/**
 * Small progressive-enhancement behaviours for the Blade layer: dropdowns, the
 * staff sidebar on small screens, tab groups and destructive-action confirms.
 * Everything degrades to plain HTML links when JavaScript is unavailable.
 */

function initDropdowns(root = document) {
    root.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdown.dataset.dropdownReady === 'true') {
            return
        }

        dropdown.dataset.dropdownReady = 'true'
        const trigger = dropdown.querySelector('[data-dropdown-trigger]')
        const menu = dropdown.querySelector('[data-dropdown-menu]')

        if (!trigger || !menu) {
            return
        }

        menu.hidden = true

        const close = () => {
            menu.hidden = true
            trigger.setAttribute('aria-expanded', 'false')
        }

        const open = () => {
            menu.hidden = false
            trigger.setAttribute('aria-expanded', 'true')
        }

        trigger.setAttribute('aria-expanded', 'false')
        trigger.setAttribute('aria-haspopup', 'true')

        trigger.addEventListener('click', (event) => {
            event.preventDefault()
            menu.hidden ? open() : close()
        })

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target)) {
                close()
            }
        })

        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close()
                trigger.focus()
            }
        })
    })
}

function initSidebar() {
    const sidebar = document.querySelector('[data-sidebar]')
    const overlay = document.querySelector('[data-sidebar-overlay]')
    const toggles = document.querySelectorAll('[data-sidebar-toggle]')

    if (!sidebar) {
        return
    }

    const setOpen = (isOpen) => {
        sidebar.classList.toggle('-translate-x-full', !isOpen)
        overlay?.classList.toggle('hidden', !isOpen)
        document.body.classList.toggle('overflow-hidden', isOpen)
    }

    setOpen(false)

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            setOpen(sidebar.classList.contains('-translate-x-full'))
        })
    })

    overlay?.addEventListener('click', () => setOpen(false))
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)))
}

function initTabs(root = document) {
    root.querySelectorAll('[data-tabs]').forEach((group) => {
        if (group.dataset.tabsReady === 'true') {
            return
        }

        group.dataset.tabsReady = 'true'

        group.querySelectorAll('[data-tab-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.tabTarget

                group.querySelectorAll('[data-tab-target]').forEach((sibling) => {
                    sibling.classList.toggle('border-brand-600', sibling === button)
                    sibling.classList.toggle('text-brand-700', sibling === button)
                    sibling.classList.toggle('border-transparent', sibling !== button)
                    sibling.classList.toggle('text-slate-500', sibling !== button)
                    sibling.setAttribute('aria-selected', sibling === button ? 'true' : 'false')
                })

                document.querySelectorAll(`[data-tab-panel="${CSS.escape(target)}"]`).forEach((panel) => {
                    panel.hidden = panel.dataset.tabPanel !== target
                })
            })
        })
    })
}

function initConfirms(root = document) {
    root.querySelectorAll('form[data-confirm]').forEach((form) => {
        if (form.dataset.confirmReady === 'true') {
            return
        }

        form.dataset.confirmReady = 'true'
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault()
            }
        })
    })
}

function initialise(root = document) {
    initDropdowns(root)
    initTabs(root)
    initConfirms(root)
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebar()
    initialise()
})

export { initialise, initDropdowns, initTabs, initConfirms }
