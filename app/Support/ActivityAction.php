<?php

namespace App\Support;

/**
 * Canonical names for the actions written to the audit trail. Keeping them in
 * one place lets the log screen offer a reliable action filter.
 */
final class ActivityAction
{
    public const BRANCH_CREATED = 'branch.created';

    public const BRANCH_UPDATED = 'branch.updated';

    public const BRANCH_DELETED = 'branch.deleted';

    public const CATALOG_CREATED = 'catalog.created';

    public const CATALOG_UPDATED = 'catalog.updated';

    public const CATALOG_DELETED = 'catalog.deleted';

    public const CATALOG_AVAILABILITY_UPDATED = 'catalog.availability_updated';

    public const PRICE_CHANGE_REQUESTED = 'price_change.requested';

    public const PRICE_CHANGE_APPROVED = 'price_change.approved';

    public const PRICE_CHANGE_REJECTED = 'price_change.rejected';

    public const PRICE_PUBLISHED = 'price.published';

    public const PRICE_SET_DIRECTLY = 'price.set_directly';

    public const TICKET_CREATED = 'ticket.created';

    public const TICKET_STATUS_CHANGED = 'ticket.status_changed';

    public const TICKET_ASSIGNED = 'ticket.assigned';

    public const TICKET_REPLIED = 'ticket.replied';

    public const TICKET_RESOLVED = 'ticket.resolved';

    public const REVIEW_CREATED = 'review.created';

    public const REVIEW_PUBLISHED = 'review.published';

    public const REVIEW_HIDDEN = 'review.hidden';

    public const REVIEW_FLAGGED = 'review.flagged';

    public const SALE_RECORDED = 'sale.recorded';

    public const SALE_IMPORTED = 'sale.imported';

    public const ANNOUNCEMENT_CREATED = 'announcement.created';

    public const ANNOUNCEMENT_UPDATED = 'announcement.updated';

    public const ANNOUNCEMENT_DELETED = 'announcement.deleted';

    public const USER_ROLE_CHANGED = 'user.role_changed';

    public const USER_CREATED = 'user.created';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::BRANCH_CREATED => 'Branch created',
            self::BRANCH_UPDATED => 'Branch updated',
            self::BRANCH_DELETED => 'Branch deleted',
            self::CATALOG_CREATED => 'Catalog item created',
            self::CATALOG_UPDATED => 'Catalog item updated',
            self::CATALOG_DELETED => 'Catalog item deleted',
            self::CATALOG_AVAILABILITY_UPDATED => 'Branch availability updated',
            self::PRICE_CHANGE_REQUESTED => 'Price change requested',
            self::PRICE_CHANGE_APPROVED => 'Price change approved',
            self::PRICE_CHANGE_REJECTED => 'Price change rejected',
            self::PRICE_PUBLISHED => 'Price published',
            self::PRICE_SET_DIRECTLY => 'Price set directly',
            self::TICKET_CREATED => 'Inquiry created',
            self::TICKET_STATUS_CHANGED => 'Inquiry status changed',
            self::TICKET_ASSIGNED => 'Inquiry assigned',
            self::TICKET_REPLIED => 'Inquiry replied',
            self::TICKET_RESOLVED => 'Inquiry resolved',
            self::REVIEW_CREATED => 'Review submitted',
            self::REVIEW_PUBLISHED => 'Review published',
            self::REVIEW_HIDDEN => 'Review hidden',
            self::REVIEW_FLAGGED => 'Review flagged',
            self::SALE_RECORDED => 'Sale recorded',
            self::SALE_IMPORTED => 'Sales imported',
            self::ANNOUNCEMENT_CREATED => 'Announcement created',
            self::ANNOUNCEMENT_UPDATED => 'Announcement updated',
            self::ANNOUNCEMENT_DELETED => 'Announcement deleted',
            self::USER_ROLE_CHANGED => 'User role changed',
            self::USER_CREATED => 'User created',
        ];
    }

    public static function label(string $action): string
    {
        return self::options()[$action] ?? str($action)->headline()->toString();
    }
}
