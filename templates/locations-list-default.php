<?php
/**
 * Default Locations List Template
 * 
 * Template for displaying multiple business locations
 * Can be overridden by theme: /theme/softworld-seo/locations-list-default.php
 * 
 * Variables available:
 * $locations - Array of location objects
 * $atts - Shortcode attributes (limit, show_map, template)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="softworld-locations-list">
    
    <?php if (empty($locations)): ?>
        <div class="no-locations-message">
            <p><?php _e('No locations found.', 'softworld-ai-seo'); ?></p>
        </div>
    <?php else: ?>
        
        <div class="locations-header">
            <h3 class="locations-title">
                <?php 
                printf(
                    _n('Our Location', 'Our %d Locations', count($locations), 'softworld-ai-seo'),
                    count($locations)
                );
                ?>
            </h3>
            <?php if (count($locations) > 1): ?>
                <div class="locations-filters">
                    <button type="button" class="filter-btn active" data-filter="all">
                        <?php _e('All Locations', 'softworld-ai-seo'); ?>
                    </button>
                    <?php
                    // Get unique business types for filtering
                    $business_types = array_unique(array_column($locations, 'business_type'));
                    foreach ($business_types as $type):
                        if ($type && $type !== 'LocalBusiness'):
                    ?>
                        <button type="button" class="filter-btn" data-filter="<?php echo esc_attr($type); ?>">
                            <?php echo esc_html(ucwords(str_replace(['_', '-'], ' ', $type))); ?>
                        </button>
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="locations-grid">
            <?php foreach ($locations as $index => $location): ?>
                <div class="location-item" 
                     data-location-id="<?php echo esc_attr($location->id); ?>"
                     data-business-type="<?php echo esc_attr($location->business_type); ?>"
                     itemscope 
                     itemtype="http://schema.org/<?php echo esc_attr($location->business_type); ?>">
                     
                    <?php if ($location->image_url): ?>
                        <div class="location-image">
                            <img src="<?php echo esc_url($location->image_url); ?>" 
                                 alt="<?php echo esc_attr($location->name); ?>"
                                 itemprop="image">
                            <?php if ($location->is_primary): ?>
                                <div class="primary-badge" title="<?php _e('Primary Location', 'softworld-ai-seo'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="location-content">
                        <div class="location-header">
                            <h4 class="location-name" itemprop="name">
                                <?php echo esc_html($location->name); ?>
                                <?php if ($location->is_primary && !$location->image_url): ?>
                                    <span class="primary-star" title="<?php _e('Primary Location', 'softworld-ai-seo'); ?>">★</span>
                                <?php endif; ?>
                            </h4>
                            
                            <?php if ($location->business_type && $location->business_type !== 'LocalBusiness'): ?>
                                <div class="business-type-badge">
                                    <?php echo esc_html(ucwords(str_replace(['_', '-'], ' ', $location->business_type))); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($location->description): ?>
                            <div class="location-description" itemprop="description">
                                <p><?php echo esc_html(wp_trim_words($location->description, 20)); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="location-address" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
                            <div class="address-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div class="address-text">
                                <div itemprop="streetAddress"><?php echo esc_html($location->address); ?></div>
                                <div>
                                    <span itemprop="addressLocality"><?php echo esc_html($location->city); ?></span>,
                                    <span itemprop="addressRegion"><?php echo esc_html($location->state); ?></span>
                                    <span itemprop="postalCode"><?php echo esc_html($location->postal_code); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($location->phone): ?>
                            <div class="location-phone">
                                <div class="phone-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                    </svg>
                                </div>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $location->phone)); ?>" 
                                   itemprop="telephone" class="phone-link">
                                    <?php echo esc_html($location->phone); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($location->email): ?>
                            <div class="location-email">
                                <div class="email-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                </div>
                                <a href="mailto:<?php echo esc_attr($location->email); ?>" 
                                   itemprop="email" class="email-link">
                                    <?php echo esc_html($location->email); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php 
                        // Show opening hours if available
                        if ($location->opening_hours): 
                            $opening_hours = json_decode($location->opening_hours, true);
                            if ($opening_hours):
                                $current_status = $this->getCurrentStatus($opening_hours);
                        ?>
                            <div class="location-hours">
                                <div class="hours-summary">
                                    <div class="hours-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
                                        </svg>
                                    </div>
                                    <div class="hours-info">
                                        <?php if ($current_status): ?>
                                            <span class="status <?php echo $current_status['is_open'] ? 'open' : 'closed'; ?>">
                                                <?php echo $current_status['is_open'] ? __('Open Now', 'softworld-ai-seo') : __('Closed Now', 'softworld-ai-seo'); ?>
                                            </span>
                                            <?php if (!empty($current_status['next_change'])): ?>
                                                <small class="next-change">
                                                    <?php echo esc_html($current_status['next_change']); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button type="button" class="hours-toggle" data-location="<?php echo $location->id; ?>">
                                            <?php _e('View Hours', 'softworld-ai-seo'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="hours-details" id="hours-<?php echo $location->id; ?>" style="display: none;">
                                    <?php echo do_shortcode('[softworld_opening_hours location_id="' . $location->id . '" format="list"]'); ?>
                                </div>
                            </div>
                        <?php endif; endif; ?>

                        <?php if ($location->price_range): ?>
                            <div class="location-price-range">
                                <span class="price-label"><?php _e('Price Range:', 'softworld-ai-seo'); ?></span>
                                <span class="price-value" itemprop="priceRange"><?php echo esc_html($location->price_range); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($atts['show_map'] === 'yes' && $location->latitude && $location->longitude): ?>
                            <div class="location-map">
                                <?php echo do_shortcode('[softworld_google_map location_id="' . $location->id . '" height="180px" zoom="16"]'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="location-actions">
                            <?php if ($location->latitude && $location->longitude): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $location->latitude; ?>,<?php echo $location->longitude; ?>" 
                                   target="_blank" class="action-btn directions-btn" rel="noopener">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.39.39-1.02 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                                    </svg>
                                    <?php _e('Directions', 'softworld-ai-seo'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($location->phone): ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $location->phone)); ?>" 
                                   class="action-btn call-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                    </svg>
                                    <?php _e('Call', 'softworld-ai-seo'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($location->website): ?>
                                <a href="<?php echo esc_url($location->website); ?>" 
                                   target="_blank" class="action-btn website-btn" rel="noopener">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                    </svg>
                                    <?php _e('Website', 'softworld-ai-seo'); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($location->latitude && $location->longitude): ?>
                            <!-- Hidden structured data for coordinates -->
                            <div style="display: none;" itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
                                <meta itemprop="latitude" content="<?php echo esc_attr($location->latitude); ?>">
                                <meta itemprop="longitude" content="<?php echo esc_attr($location->longitude); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($locations) > 3): ?>
            <div class="locations-footer">
                <button type="button" class="load-more-btn" style="display: none;">
                    <?php _e('Load More Locations', 'softworld-ai-seo'); ?>
                </button>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Location filtering
    $('.filter-btn').on('click', function() {
        var filter = $(this).data('filter');
        
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.location-item').each(function() {
            var businessType = $(this).data('business-type');
            
            if (filter === 'all' || businessType === filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Hours toggle
    $('.hours-toggle').on('click', function() {
        var locationId = $(this).data('location');
        var hoursDiv = $('#hours-' + locationId);
        
        if (hoursDiv.is(':visible')) {
            hoursDiv.slideUp();
            $(this).text('<?php _e('View Hours', 'softworld-ai-seo'); ?>');
        } else {
            hoursDiv.slideDown();
            $(this).text('<?php _e('Hide Hours', 'softworld-ai-seo'); ?>');
        }
    });
    
    // Load more functionality
    if ($('.location-item').length > 6) {
        $('.location-item:gt(5)').hide();
        $('.load-more-btn').show();
        
        $('.load-more-btn').on('click', function() {
            $('.location-item:hidden:lt(3)').slideDown();
            
            if ($('.location-item:hidden').length === 0) {
                $(this).hide();
            }
        });
    }
});
</script>

<style>
.softworld-locations-list {
    margin-bottom: 24px;
}

.locations-header {
    margin-bottom: 24px;
    text-align: center;
}

.locations-title {
    color: #1a202c;
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 16px 0;
    line-height: 1.2;
}

.locations-filters {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-btn {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    color: #4a5568;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    font-weight: 500;
}

.filter-btn:hover,
.filter-btn.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.location-item {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.location-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.location-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.location-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.location-item:hover .location-image img {
    transform: scale(1.05);
}

.location-image .primary-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #f59e0b;
    color: white;
    padding: 6px;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.location-content {
    padding: 20px;
}

.location-header {
    margin-bottom: 12px;
}

.location-name {
    color: #1a202c;
    font-size: 22px;
    font-weight: 600;
    margin: 0 0 8px 0;
    line-height: 1.3;
    display: flex;
    align-items: center;
    gap: 8px;
}

.primary-star {
    color: #f59e0b;
    font-size: 18px;
}

.business-type-badge {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.location-description {
    color: #6b7280;
    margin-bottom: 16px;
    line-height: 1.5;
}

.location-address,
.location-phone,
.location-email {
    display: flex;
    align-items: flex-start;
    margin-bottom: 8px;
    font-size: 14px;
}

.location-address svg,
.location-phone svg,
.location-email svg,
.location-hours svg {
    margin-right: 8px;
    margin-top: 2px;
    color: #9ca3af;
    flex-shrink: 0;
}

.location-address .address-text,
.location-phone,
.location-email {
    color: #4b5563;
}

.phone-link,
.email-link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
}

.phone-link:hover,
.email-link:hover {
    text-decoration: underline;
}

.location-hours {
    margin: 12px 0;
    padding: 12px 0;
    border-top: 1px solid #f3f4f6;
}

.hours-summary {
    display: flex;
    align-items: center;
}

.hours-info {
    flex: 1;
    margin-left: 8px;
}

.status {
    font-weight: 600;
    font-size: 14px;
    display: block;
}

.status.open {
    color: #059669;
}

.status.closed {
    color: #dc2626;
}

.next-change {
    display: block;
    color: #6b7280;
    font-size: 12px;
    margin-top: 2px;
}

.hours-toggle {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 12px;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    margin-top: 4px;
}

.hours-details {
    margin-top: 12px;
}

.location-price-range {
    margin: 8px 0;
    font-size: 14px;
}

.price-label {
    color: #6b7280;
    font-weight: 500;
}

.price-value {
    color: #059669;
    font-weight: 600;
    margin-left: 8px;
}

.location-map {
    margin: 16px 0;
}

.location-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.directions-btn {
    background: #2563eb;
    color: white;
}

.directions-btn:hover {
    background: #1d4ed8;
    color: white;
    text-decoration: none;
}

.call-btn {
    background: #059669;
    color: white;
}

.call-btn:hover {
    background: #047857;
    color: white;
    text-decoration: none;
}

.website-btn {
    background: #f3f4f6;
    color: #4b5563;
    border-color: #d1d5db;
}

.website-btn:hover {
    background: #e5e7eb;
    color: #374151;
    text-decoration: none;
}

.locations-footer {
    text-align: center;
    margin-top: 32px;
}

.load-more-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.load-more-btn:hover {
    background: #1d4ed8;
}

.no-locations-message {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

/* Responsive Design */
@media (max-width: 768px) {
    .locations-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .location-content {
        padding: 16px;
    }
    
    .locations-title {
        font-size: 28px;
    }
    
    .location-name {
        font-size: 20px;
    }
    
    .locations-filters {
        justify-content: flex-start;
        overflow-x: auto;
        padding-bottom: 8px;
    }
    
    .filter-btn {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .location-actions {
        flex-direction: column;
    }
    
    .action-btn {
        justify-content: center;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .softworld-locations-list {
        color: #f3f4f6;
    }
    
    .locations-title,
    .location-name {
        color: #f9fafb;
    }
    
    .location-item {
        background: #1f2937;
        border-color: #374151;
    }
    
    .location-description,
    .location-address .address-text,
    .next-change {
        color: #d1d5db;
    }
    
    .location-hours,
    .location-actions {
        border-top-color: #4b5563;
    }
    
    .filter-btn {
        background: #374151;
        border-color: #4b5563;
        color: #d1d5db;
    }
    
    .website-btn {
        background: #374151;
        color: #d1d5db;
        border-color: #4b5563;
    }
    
    .website-btn:hover {
        background: #4b5563;
        color: #f3f4f6;
    }
}
</style>

<?php
// Helper method to get current status (would need to be added to the Local SEO Manager class)
function getCurrentStatus($opening_hours) {
    if (!$opening_hours) return null;
    
    $now = new DateTime();
    $current_day = strtolower($now->format('l'));
    $current_time = $now->format('H:i');
    
    if (!isset($opening_hours[$current_day])) return null;
    
    $today_hours = $opening_hours[$current_day];
    
    if (!empty($today_hours['closed'])) {
        return [
            'is_open' => false,
            'next_change' => null
        ];
    }
    
    $open = $today_hours['open'] ?? null;
    $close = $today_hours['close'] ?? null;
    
    if (!$open || !$close) return null;
    
    $is_open = ($current_time >= $open && $current_time <= $close);
    
    return [
        'is_open' => $is_open,
        'next_change' => $is_open 
            ? sprintf(__('Closes at %s', 'softworld-ai-seo'), $close)
            : sprintf(__('Opens at %s', 'softworld-ai-seo'), $open)
    ];
}
?>