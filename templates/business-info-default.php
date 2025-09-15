<?php
/**
 * Default Business Info Template
 * 
 * Template for displaying business information
 * Can be overridden by theme: /theme/softworld-seo/business-info-default.php
 * 
 * Variables available:
 * $location - Location object with all business data
 * $show - What fields to show (all, name, address, phone, email, website)
 */

if (!defined('ABSPATH')) {
    exit;
}

$show_fields = explode(',', $show);
$show_fields = array_map('trim', $show_fields);
?>

<div class="softworld-business-info" itemscope itemtype="http://schema.org/<?php echo esc_attr($location->business_type); ?>">
    
    <?php if ($show === 'all' || in_array('name', $show_fields)): ?>
        <h3 class="business-name" itemprop="name">
            <?php echo esc_html($location->name); ?>
            <?php if ($location->is_primary): ?>
                <span class="primary-badge" title="<?php _e('Primary Location', 'softworld-ai-seo'); ?>">★</span>
            <?php endif; ?>
        </h3>
    <?php endif; ?>

    <?php if ($location->description && ($show === 'all' || in_array('description', $show_fields))): ?>
        <div class="business-description" itemprop="description">
            <p><?php echo esc_html($location->description); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show === 'all' || in_array('address', $show_fields)): ?>
        <div class="business-address" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
            <div class="address-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </div>
            <div class="address-content">
                <div class="street-address" itemprop="streetAddress">
                    <?php echo esc_html($location->address); ?>
                </div>
                <div class="city-state-zip">
                    <span itemprop="addressLocality"><?php echo esc_html($location->city); ?></span>,
                    <span itemprop="addressRegion"><?php echo esc_html($location->state); ?></span>
                    <span itemprop="postalCode"><?php echo esc_html($location->postal_code); ?></span>
                </div>
                <?php if ($location->country): ?>
                    <div class="country" itemprop="addressCountry">
                        <?php echo esc_html($location->country); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($show === 'all' || in_array('phone', $show_fields)) && $location->phone): ?>
        <div class="business-phone">
            <div class="phone-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                </svg>
            </div>
            <div class="phone-content">
                <strong><?php _e('Phone:', 'softworld-ai-seo'); ?></strong>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $location->phone)); ?>" 
                   itemprop="telephone" class="phone-link">
                    <?php echo esc_html($location->phone); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($show === 'all' || in_array('email', $show_fields)) && $location->email): ?>
        <div class="business-email">
            <div class="email-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>
            <div class="email-content">
                <strong><?php _e('Email:', 'softworld-ai-seo'); ?></strong>
                <a href="mailto:<?php echo esc_attr($location->email); ?>" 
                   itemprop="email" class="email-link">
                    <?php echo esc_html($location->email); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($show === 'all' || in_array('website', $show_fields)) && $location->website): ?>
        <div class="business-website">
            <div class="website-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                </svg>
            </div>
            <div class="website-content">
                <strong><?php _e('Website:', 'softworld-ai-seo'); ?></strong>
                <a href="<?php echo esc_url($location->website); ?>" 
                   itemprop="url" class="website-link" target="_blank" rel="noopener">
                    <?php echo esc_html($location->website); ?>
                    <span class="external-link-icon">↗</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($location->price_range && ($show === 'all' || in_array('price', $show_fields))): ?>
        <div class="business-price-range">
            <strong><?php _e('Price Range:', 'softworld-ai-seo'); ?></strong>
            <span class="price-range" itemprop="priceRange"><?php echo esc_html($location->price_range); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($location->serves_cuisine && ($show === 'all' || in_array('cuisine', $show_fields))): ?>
        <div class="business-cuisine">
            <strong><?php _e('Cuisine:', 'softworld-ai-seo'); ?></strong>
            <span itemprop="servesCuisine"><?php echo esc_html($location->serves_cuisine); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($location->payment_accepted && ($show === 'all' || in_array('payment', $show_fields))): ?>
        <div class="business-payment">
            <strong><?php _e('Payment Methods:', 'softworld-ai-seo'); ?></strong>
            <span itemprop="paymentAccepted"><?php echo esc_html($location->payment_accepted); ?></span>
        </div>
    <?php endif; ?>

    <?php 
    // Add opening hours if available and requested
    if ($location->opening_hours && ($show === 'all' || in_array('hours', $show_fields))): 
        $opening_hours = json_decode($location->opening_hours, true);
        if ($opening_hours):
    ?>
        <div class="business-hours">
            <strong><?php _e('Hours:', 'softworld-ai-seo'); ?></strong>
            <?php echo do_shortcode('[softworld_opening_hours location_id="' . $location->id . '" format="list" show_closed="no"]'); ?>
        </div>
    <?php endif; endif; ?>

    <?php if ($location->latitude && $location->longitude && ($show === 'all' || in_array('map', $show_fields))): ?>
        <div class="business-map">
            <?php echo do_shortcode('[softworld_google_map location_id="' . $location->id . '" height="200px" zoom="16"]'); ?>
        </div>
    <?php endif; ?>

    <?php if ($show === 'all' || in_array('directions', $show_fields)): ?>
        <div class="business-actions">
            <?php if ($location->latitude && $location->longitude): ?>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $location->latitude; ?>,<?php echo $location->longitude; ?>" 
                   target="_blank" class="directions-link button" rel="noopener">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.39.39-1.02 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                    </svg>
                    <?php _e('Get Directions', 'softworld-ai-seo'); ?>
                </a>
            <?php elseif ($location->address): ?>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($location->address . ', ' . $location->city . ', ' . $location->state); ?>" 
                   target="_blank" class="directions-link button" rel="noopener">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.39.39-1.02 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                    </svg>
                    <?php _e('Get Directions', 'softworld-ai-seo'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($location->latitude && $location->longitude): ?>
        <!-- Hidden structured data for coordinates -->
        <div style="display: none;" itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
            <meta itemprop="latitude" content="<?php echo esc_attr($location->latitude); ?>">
            <meta itemprop="longitude" content="<?php echo esc_attr($location->longitude); ?>">
        </div>
    <?php endif; ?>

</div>

<style>
.softworld-business-info {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e1e5e9;
    margin-bottom: 24px;
    position: relative;
}

.softworld-business-info .business-name {
    color: #1a202c;
    margin: 0 0 16px 0;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 8px;
}

.softworld-business-info .primary-badge {
    color: #f59e0b;
    font-size: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}

.softworld-business-info .business-description {
    margin-bottom: 20px;
    color: #4a5568;
    font-size: 16px;
    line-height: 1.5;
}

.softworld-business-info > div:not(.business-name):not(.business-description) {
    display: flex;
    align-items: flex-start;
    margin-bottom: 12px;
    padding: 8px 0;
}

.softworld-business-info > div:last-child {
    margin-bottom: 0;
}

.softworld-business-info svg {
    flex-shrink: 0;
    margin-right: 12px;
    margin-top: 2px;
    color: #6b7280;
}

.softworld-business-info .address-content,
.softworld-business-info .phone-content,
.softworld-business-info .email-content,
.softworld-business-info .website-content {
    flex: 1;
}

.softworld-business-info strong {
    display: block;
    color: #374151;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.softworld-business-info a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.softworld-business-info a:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.softworld-business-info .external-link-icon {
    margin-left: 4px;
    font-size: 12px;
    opacity: 0.7;
}

.softworld-business-info .city-state-zip {
    color: #6b7280;
}

.softworld-business-info .business-map {
    margin-top: 20px;
    margin-bottom: 16px;
}

.softworld-business-info .business-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.softworld-business-info .directions-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563eb;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s;
}

.softworld-business-info .directions-link:hover {
    background: #1d4ed8;
    color: white;
    text-decoration: none;
}

.softworld-business-info .business-price-range,
.softworld-business-info .business-cuisine,
.softworld-business-info .business-payment {
    display: block;
    margin-bottom: 8px;
}

.softworld-business-info .price-range {
    color: #059669;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .softworld-business-info {
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .softworld-business-info .business-name {
        font-size: 24px;
    }
    
    .softworld-business-info > div:not(.business-name):not(.business-description) {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .softworld-business-info svg {
        margin-right: 8px;
        margin-bottom: 4px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .softworld-business-info {
        background: #1f2937;
        border-color: #374151;
        color: #f3f4f6;
    }
    
    .softworld-business-info .business-name {
        color: #f9fafb;
    }
    
    .softworld-business-info .business-description {
        color: #d1d5db;
    }
    
    .softworld-business-info strong {
        color: #e5e7eb;
    }
    
    .softworld-business-info .city-state-zip {
        color: #9ca3af;
    }
    
    .softworld-business-info .business-actions {
        border-top-color: #4b5563;
    }
}
</style>