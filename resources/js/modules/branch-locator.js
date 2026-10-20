/**
 * Branch locator: OpenStreetMap tiles via Leaflet, Nominatim for address
 * geocoding, OSRM for driving routes, and Turf for great-circle distance.
 *
 * Every provider value arrives through data attributes on the [data-locator]
 * element so config/maps.php stays the single source of truth. No API key is
 * required for any part of this.
 */
import { distance } from '@turf/distance'
import { point } from '@turf/helpers'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const BRAND = '#0d9488'
const BRAND_DARK = '#0f766e'
const FALLBACK_VIEW = [14.5995, 120.9842]
const FALLBACK_ZOOM = 7

/**
 * A teal pin drawn as a divIcon. Avoids Leaflet's default marker sprite, whose
 * asset paths bundlers routinely break, and keeps the marker in the brand
 * palette without shipping an image.
 */
function branchIcon() {
    return L.divIcon({
        className: 'oiland-branch-marker',
        html: '<span class="oiland-branch-marker__dot"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -10],
    })
}

function nearestBranchIcon() {
    return L.divIcon({
        className: 'oiland-branch-marker oiland-branch-marker--nearest',
        html: '<span class="oiland-branch-marker__dot"></span>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        popupAnchor: [0, -14],
    })
}

function readJson(element, attribute, fallback) {
    const raw = element.getAttribute(attribute)

    if (!raw) {
        return fallback
    }

    try {
        return JSON.parse(raw)
    } catch {
        console.warn(`[locator] could not parse ${attribute}`)

        return fallback
    }
}

function formatDistance(metres) {
    if (!Number.isFinite(metres)) {
        return '—'
    }

    return `${(metres / 1000).toFixed(1)} km`
}

function formatDuration(seconds) {
    if (!Number.isFinite(seconds)) {
        return '—'
    }

    const minutes = Math.round(seconds / 60)

    return minutes < 60 ? `${minutes} min` : `${Math.floor(minutes / 60)} h ${minutes % 60} min`
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

function buildPopupHtml(branch) {
    return `
        <div class="min-w-48 max-w-64 font-sans">
            <p class="text-sm font-semibold text-slate-900">${escapeHtml(branch.name)}</p>
            <p class="mt-0.5 text-xs text-slate-500">${escapeHtml(branch.address)}</p>
            ${
                branch.average_rating
                    ? `<p class="mt-1 text-xs font-medium text-amber-600">${Number(branch.average_rating).toFixed(1)} / 5 (${Number(
                          branch.reviews_count ?? 0,
                      )})</p>`
                    : ''
            }
            <a class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:underline" href="${escapeHtml(
                branch.url,
            )}">View station details</a>
        </div>
    `
}

function initLocator(root) {
    const branches = readJson(root, 'data-branches', [])

    const config = {
        tileUrl: root.dataset.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        tileAttribution: root.dataset.tileAttribution || '&copy; OpenStreetMap contributors',
        maxZoom: Number(root.dataset.maxZoom) || 19,
        nominatimUrl: root.dataset.nominatimUrl || '',
        osrmUrl: root.dataset.osrmUrl || '',
    }

    const initialView = [
        Number(root.dataset.initialLat) || FALLBACK_VIEW[0],
        Number(root.dataset.initialLng) || FALLBACK_VIEW[1],
    ]
    const initialZoom = Number(root.dataset.initialZoom) || FALLBACK_ZOOM

    // This is only the initial map view. Nearest-station results are shown
    // after the browser supplies the visitor's actual location.
    const userLocation = [initialView[0], initialView[1]]

    const map = L.map(root, { scrollWheelZoom: false }).setView(initialView, initialZoom)

    L.tileLayer(config.tileUrl, {
        attribution: config.tileAttribution,
        maxZoom: config.maxZoom,
    }).addTo(map)

    const markers = new Map()
    const routeLayer = L.layerGroup().addTo(map)
    let nearestMarkerId = null

    branches.forEach((branch) => {
        if (!Number.isFinite(Number(branch.latitude)) || !Number.isFinite(Number(branch.longitude))) {
            return
        }

        const marker = L.marker([branch.latitude, branch.longitude], { icon: branchIcon() }).addTo(map)

        marker.bindPopup(() => buildPopupHtml(branch))
        marker.on('click', () => {
            root.dispatchEvent(new CustomEvent('locator:branch-selected', { detail: branch, bubbles: true }))
        })

        markers.set(branch.id, marker)
    })

    const search = document.querySelector('[data-locator-search]')
    const status = document.querySelector('[data-locator-status]')
    const locateButton = document.querySelector('[data-locator-locate]')
    const resultsList = document.querySelector('[data-locator-results]')
    const routePanel = document.querySelector('[data-locator-route]')
    const routeSummary = document.querySelector('[data-locator-route-summary]')
    const routeSteps = document.querySelector('[data-locator-route-steps]')
    let userMarker = null
    const locateButtonContent = locateButton?.innerHTML ?? ''
    let addressSearchTimer
    let addressSearchVersion = 0

    function setStatus(message, tone = 'muted') {
        if (!status) {
            return
        }

        status.textContent = message
        status.className =
            'text-xs ' +
            {
                muted: 'text-slate-500',
                busy: 'text-slate-500',
                error: 'text-rose-600',
                ok: 'text-emerald-600',
            }[tone]
    }

    function setInitialDistanceLabel(label) {
        resultsList?.querySelectorAll('[data-locator-distance]').forEach((element) => {
            element.textContent = label
        })
    }

    function setLocateLoading(isLoading) {
        if (!locateButton) return

        locateButton.disabled = isLoading
        locateButton.setAttribute('aria-busy', String(isLoading))
        locateButton.innerHTML = isLoading
            ? '<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path></svg><span>Finding your location…</span>'
            : locateButtonContent
    }

    function setUserMarker(position) {
        userMarker?.remove()
        userMarker = L.circleMarker(position, {
            radius: 9,
            color: '#ffffff',
            weight: 3,
            fillColor: '#2563eb',
            fillOpacity: 0.95,
        })
            .addTo(map)
            .bindPopup('Your searched location')
    }

    function statusLine(branch) {
        const isOpen = Boolean(branch.is_open_now)
        const state = isOpen ? 'Open now' : 'Closed now'
        const color = isOpen ? 'text-emerald-700' : 'text-rose-700'

        return `<p class="mt-1 truncate text-xs font-medium ${color}">${state} · ${escapeHtml(branch.hours_today || 'Hours unavailable')}</p>`
    }

    function sortByDistance(origin) {
        const sorted = branches.map((branch) => {
            const latitude = Number(branch.latitude)
            const longitude = Number(branch.longitude)
            const hasCoordinates = Number.isFinite(latitude) && Number.isFinite(longitude)

            return {
                ...branch,
                latitude: hasCoordinates ? latitude : null,
                longitude: hasCoordinates ? longitude : null,
                metres: hasCoordinates
                    ? distance(point(origin[1], origin[0]), point(longitude, latitude), { units: 'kilometers' }) * 1000
                    : Number.POSITIVE_INFINITY,
            }
        }).sort((a, b) => a.metres - b.metres)

        if (!resultsList) {
            return sorted
        }

        nearestMarkerId = sorted[0]?.id ?? null
        markers.forEach((marker, id) => marker.setIcon(id === nearestMarkerId ? nearestBranchIcon() : branchIcon()))

        resultsList.innerHTML = ''
        sorted.forEach((branch, index) => {
            const item = document.createElement('li')
            item.className = `border-b border-slate-100 py-3 last:border-0 ${index === 0 ? 'rounded-lg bg-brand-50/70 px-3 ring-1 ring-inset ring-brand-100' : ''}`
            item.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        ${index === 0 ? '<p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-brand-800">Nearest station</p>' : ''}
                        <p class="truncate text-sm font-semibold text-slate-900">${index + 1}. ${escapeHtml(branch.name)}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-sm font-bold text-brand-800 shadow-sm">${Number.isFinite(branch.metres) ? formatDistance(branch.metres) : 'Distance unavailable'}</span>
                </div>
                <p class="mt-1 truncate text-xs text-slate-500">${escapeHtml(branch.address)}</p>
                <div class="mt-1 flex flex-wrap items-center gap-x-1 text-xs">
                    <span class="font-semibold text-amber-600">${Number(branch.average_rating ?? 0).toFixed(1)} ★</span>
                    <span class="text-slate-400">(${Number(branch.reviews_count ?? 0)} reviews)</span>
                    <span class="text-slate-300">·</span>
                    ${statusLine(branch)}
                </div>
            `
            if (branch.latitude !== null && branch.longitude !== null) {
                item.classList.add('cursor-pointer')
                item.addEventListener('click', () => {
                    map.setView([branch.latitude, branch.longitude], 15)
                    markers.get(branch.id)?.openPopup()
                })
            }
            resultsList.appendChild(item)
        })

        return sorted
    }

    async function drawRoute(branch) {
        if (!routePanel || !branch.latitude || !branch.longitude) {
            return
        }

        setStatus('Calculating the drive…', 'busy')
        routePanel.classList.remove('hidden')

        try {
            const coordinates = `${userLocation[1]},${userLocation[0]};${branch.longitude},${branch.latitude}`
            const response = await fetch(
                `${config.osrmUrl.replace(/\/$/, '')}/route/v1/driving/${coordinates}?overview=full&geometries=geojson&steps=true`,
            )
            const payload = await response.json()

            const route = payload.routes?.[0]

            if (!route) {
                throw new Error('no route')
            }

            routeLayer.clearLayers()
            L.geoJSON(route.geometry, { color: BRAND, weight: 4, opacity: 0.9 }).addTo(routeLayer)
            map.fitBounds(routeLayer.getBounds().pad(0.2))

            const straightLine =
                distance(point(userLocation[1], userLocation[0]), point(branch.longitude, branch.latitude), {
                    units: 'kilometers',
                }) * 1000

            if (routeSummary) {
                routeSummary.textContent = `${formatDistance(straightLine)} away · ${formatDistance(
                    route.distance,
                )} by road · about ${formatDuration(route.duration)}`
            }

            if (routeSteps) {
                routeSteps.innerHTML = ''
                route.legs?.[0]?.steps
                    ?.filter((step) => step.instruction)
                    .forEach((step) => {
                        const item = document.createElement('li')
                        item.className = 'border-b border-slate-100 py-2 text-sm text-slate-700 last:border-0'
                        item.textContent = step.instruction
                        routeSteps.appendChild(item)
                    })
            }

            setStatus('Route ready.', 'ok')
        } catch (error) {
            console.error('[locator] routing failed', error)
            setStatus('Could not calculate a driving route right now.', 'error')
        }
    }

    locateButton?.addEventListener('click', async () => {
        if (!navigator.geolocation) {
            setStatus('This browser cannot share your location.', 'error')

            return
        }

        if (window.isSecureContext === false) {
            setStatus('Location access requires a secure connection. Open this site on HTTPS or localhost.', 'error')

            return
        }

        setLocateLoading(true)
        setInitialDistanceLabel('Finding distance…')
        setStatus('Waiting for your browser to provide a location. If no permission prompt appears, check this site’s location permission in browser settings.', 'busy')

        // When the browser already has a blocked permission, getCurrentPosition
        // will fail without showing a prompt. Explain how to recover first.
        try {
            const permission = await navigator.permissions?.query({ name: 'geolocation' })
            if (permission?.state === 'denied') {
                setLocateLoading(false)
                setInitialDistanceLabel('Location permission blocked')
                setStatus('Location is blocked for this site. Allow location in your browser’s site settings, then try again.', 'error')

                return
            }
        } catch {
            // Permissions API is optional; requesting a position still works.
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                setLocateLoading(false)
                userLocation[0] = position.coords.latitude
                userLocation[1] = position.coords.longitude

                setUserMarker(userLocation)
                map.setView(userLocation, 13)

                const sorted = sortByDistance(userLocation)
                setStatus(
                    sorted[0]
                        ? `Nearest station: ${sorted[0].name} · ${formatDistance(sorted[0].metres)} away. Stations below are sorted nearest to farthest.`
                        : 'No stations with map coordinates were found.',
                    sorted[0] ? 'ok' : 'error',
                )

                if (sorted[0]) {
                    await drawRoute(sorted[0])
                    markers.get(sorted[0].id)?.openPopup()
                    setStatus(
                        `Nearest station: ${sorted[0].name} · ${formatDistance(sorted[0].metres)} away. Stations below are sorted nearest to farthest.`,
                        'ok',
                    )
                }
            },
            (error) => {
                setLocateLoading(false)

                const message = {
                    1: 'Location access was blocked. Allow it for this site in your browser’s site settings, then try again.',
                    2: 'Your device could not determine a location. Try again or search by address.',
                    3: 'No location was returned in time. Check the browser permission prompt or use address search instead.',
                }[error.code] ?? 'Your location is unavailable. Search by address instead.'

                setInitialDistanceLabel('Location unavailable')
                setStatus(message, 'error')
            },
            { enableHighAccuracy: false, maximumAge: 60000, timeout: 12000 },
        )
    })

    async function searchAddress(query) {
        query = query.trim()
        if (query.length < 3) {
            setInitialDistanceLabel('Enter a longer location')
            return
        }

        const version = ++addressSearchVersion
        setInitialDistanceLabel('Finding distance…')
        setStatus('Searching addresses…', 'busy')

        try {
            const url = new URL(config.nominatimUrl, window.location.origin)
            url.searchParams.set('q', query)
            const response = await fetch(url, { headers: { Accept: 'application/json' } })

            if (!response.ok) {
                throw new Error(`Geocoding request failed (${response.status})`)
            }

            const match = await response.json()

            if (version !== addressSearchVersion || search?.value.trim() !== query) {
                return
            }

            if (!match?.found || !Number.isFinite(Number(match.latitude)) || !Number.isFinite(Number(match.longitude))) {
                setInitialDistanceLabel('Location not found')
                setStatus('Address not found. Check the spelling and include the city or province.', 'error')

                return
            }

            userLocation[0] = Number(match.latitude)
            userLocation[1] = Number(match.longitude)

            setUserMarker(userLocation)
            map.setView(userLocation, 13)

            const sorted = sortByDistance(userLocation)
            setStatus(
                sorted[0]
                    ? `Nearest station: ${sorted[0].name} · ${formatDistance(sorted[0].metres)} away. Stations below are sorted nearest to farthest.`
                    : 'No stations with map coordinates were found.',
                sorted[0] ? 'ok' : 'error',
            )

            if (sorted[0]) {
                await drawRoute(sorted[0])
                if (version === addressSearchVersion) {
                    markers.get(sorted[0].id)?.openPopup()
                    setStatus(
                        `Nearest station: ${sorted[0].name} · ${formatDistance(sorted[0].metres)} away. Stations below are sorted nearest to farthest.`,
                        'ok',
                    )
                }
            }
        } catch (error) {
            if (version !== addressSearchVersion) {
                return
            }

            console.error('[locator] geocoding failed', error)
            setInitialDistanceLabel('Distance unavailable')
            setStatus('Address search failed. Please try again.', 'error')
        }
    }

    search?.addEventListener('input', (event) => {
        window.clearTimeout(addressSearchTimer)
        addressSearchVersion += 1

        const query = event.target.value.trim()
        if (query.length < 3) {
            setInitialDistanceLabel('Enter a location')
            return
        }

        setInitialDistanceLabel('Finding distance…')
        addressSearchTimer = window.setTimeout(() => searchAddress(query), 800)
    })

    const initialSearch = root.dataset.initialSearch?.trim()
    if (initialSearch) {
        if (search) {
            search.value = initialSearch
        }

        searchAddress(initialSearch)
    }

}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-locator]').forEach(initLocator)
})
