# WP Match Free

**Privacy-first WordPress dating plugin with profiles, discovery, messaging, and moderation.**

[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![Tested up to](https://img.shields.io/badge/Tested%20up%20to-6.7-green.svg)](https://wordpress.org/download/)

## Description

WP Match Free transforms your WordPress site into a feature-rich dating platform with a focus on privacy, safety, and user experience. Built with modern WordPress standards and comprehensive moderation tools.

### Key Features

- **👤 Profile Management** - Complete dating profiles with photos, interests, and verification
- **🔍 Smart Discovery** - Advanced search with age, location, and interest filters
- **💌 Private Messaging** - Secure in-platform messaging system with rate limiting
- **❤️ Like System** - Express interest with mutual match detection
- **🛡️ Safety First** - Blocking, reporting, word filters, and photo moderation
- **⚖️ GDPR Compliant** - Full WordPress privacy tools integration
- **🌐 Translation Ready** - Complete internationalization support
- **📱 REST API** - Modern API for custom integrations and mobile apps

## Installation

### Automatic Installation

1. Go to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "WP Match Free"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/wpmatch-free/`
3. Activate through the **Plugins** menu in WordPress
4. Go to **WP Match > Settings** to configure

### Requirements

- **WordPress:** 6.5 or higher
- **PHP:** 8.1 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.3+)
- **Storage:** ~2MB plugin files + database space for user data

## Quick Start

### 1. Basic Setup

After activation:

1. Visit **WP Match > Settings** in your WordPress admin
2. Configure moderation settings and rate limits
3. Set up user roles and capabilities as needed

### 2. Create Dating Pages

Create these essential pages with the provided shortcodes:

**Profile Management Page:**
```
[wpmf_profile_edit]
```

**Dating Discovery Page:**
```
[wpmf_search_form]
[wpmf_search_results]
```

### 3. User Registration

- Users can register through standard WordPress registration
- They'll automatically get dating capabilities
- First-time users will be prompted to complete their dating profile

## Shortcodes

### `[wpmf_profile_edit]`
Displays the profile editing form for logged-in users.

**Features:**
- Gender and orientation selection
- Age and location settings
- Headline and biography fields
- Photo upload integration
- Privacy controls

### `[wpmf_search_form]`
Shows the dating search form with filters.

**Available Filters:**
- Age range (min/max)
- Geographic region
- Photo requirement
- Verification status
- Custom interests

### `[wpmf_search_results]`
Displays search results based on current filters.

**Features:**
- Paginated results
- Profile cards with key information
- Respect for blocking relationships
- Privacy-aware display

## Gutenberg Blocks

All shortcodes are available as native Gutenberg blocks:

- **Profile Edit Block** (`wpmf/profile-edit`)
- **Search Form Block** (`wpmf/search-form`) 
- **Search Results Block** (`wpmf/search-results`)

Access via the block editor under the "Dating" category.

## REST API

### Base URL
`https://yoursite.com/wp-json/wpmatch-free/v1/`

### Endpoints

#### `GET /profiles`
List all active dating profiles with optional filtering.

**Parameters:**
- `page` (int) - Page number for pagination
- `per_page` (int) - Results per page (max 50)
- `age_min` (int) - Minimum age filter
- `age_max` (int) - Maximum age filter
- `region` (string) - Geographic region filter
- `verified` (bool) - Verified profiles only
- `has_photo` (bool) - Profiles with photos only

**Example:**
```bash
curl "https://yoursite.com/wp-json/wpmatch-free/v1/profiles?age_min=25&age_max=35&region=europe"
```

#### `GET /profiles/{user_id}`
Get detailed profile information for a specific user.

**Authentication:** None required for public profiles
**Access Control:** Respects blocking relationships

#### `GET /matches/me` 🔒
Get potential matches for the current authenticated user.

**Authentication:** Required
**Algorithm:** Based on region compatibility and mutual interests

#### `GET /likes/me` 🔒
Get list of users who have liked your profile.

**Authentication:** Required
**Privacy:** Only shows mutual interests when appropriate

## Admin Interface

### Settings Page
**Location:** WP Admin > WP Match > Settings

Configure core plugin functionality:
- Photo moderation workflow
- Rate limiting controls
- Word filtering rules
- Verification requirements

### Moderation Tools

#### Photo Moderation
**Location:** WP Admin > WP Match > Photos

- Review uploaded photos
- Approve/reject with moderation notes
- Bulk actions for efficient moderation
- Pre-approval or post-approval workflows

#### User Reports
**Location:** WP Admin > WP Match > Reports

- Handle user reports and complaints
- Track moderation history
- Take appropriate actions
- Community safety management

#### Verifications
**Location:** WP Admin > WP Match > Verifications

- Process identity verification requests
- Manage verification workflow
- Assign verified badges
- Maintain verification audit trail

## Safety Features

### Rate Limiting
- **Messages:** 20 per day (configurable)
- **Likes:** 50 per day (configurable)
- Per-user tracking with daily reset
- Prevents spam and abuse

### Content Moderation
- **Word Filter:** Configurable blocked word list
- **Photo Moderation:** Pre or post-approval workflows
- **User Reports:** Community-driven safety reporting
- **Blocking System:** Users can block each other

### Access Controls
- Blocked users are hidden from search results
- Profile access restrictions for blocked users
- Message filtering between blocked users
- Privacy-first design principles

## Privacy & GDPR

### WordPress Privacy Tools Integration
The plugin integrates seamlessly with WordPress privacy tools:

- **Data Export:** Complete user data in standard WordPress format
- **Data Erasure:** Comprehensive deletion of all dating-related data
- **Privacy Policy:** Auto-generated content suggestions

### Data Collected
- Dating profile information (age, location, interests)
- Uploaded photos and verification documents
- Messages and communication history
- Activity data (likes, matches, last active)

### Data Processing
All data processing follows GDPR principles:
- **Lawful Basis:** User consent for dating activities
- **Data Minimization:** Only collect necessary information
- **Storage Limitation:** Configurable data retention
- **User Rights:** Full export and deletion capabilities

## User Roles & Capabilities

### Default Roles

#### Member (`wpmf_member`)
Standard dating site users with basic capabilities:
- `dating_edit_profile` - Manage their dating profile
- `dating_upload_photo` - Upload profile photos
- `dating_message` - Send and receive messages
- `dating_like` - Like other profiles
- `dating_block` - Block other users
- `dating_report` - Report inappropriate content

#### Moderator (`wpmf_moderator`)
Extends Member role with moderation capabilities:
- `dating_moderate` - Access moderation tools
- `dating_verify` - Process verification requests
- `dating_view_reports` - Handle user reports
- `dating_use_advanced_filters` - Advanced search options

### Custom Capabilities
The plugin adds these custom capabilities to WordPress:
- `dating_edit_profile`
- `dating_upload_photo`
- `dating_message`
- `dating_like`
- `dating_block`
- `dating_report`
- `dating_moderate`
- `dating_verify`
- `dating_view_reports`
- `dating_use_advanced_filters`

## Database Architecture

### Custom Tables (11 total)

The plugin uses custom database tables for optimal performance:

```sql
wp_wpmf_profiles         -- Core dating profiles
wp_wpmf_profile_meta     -- Extended profile metadata
wp_wpmf_photos           -- Profile photos with moderation
wp_wpmf_threads          -- Message conversation threads
wp_wpmf_messages         -- Individual messages
wp_wpmf_likes            -- User likes/favorites
wp_wpmf_blocks           -- User blocking relationships
wp_wpmf_reports          -- User reports for moderation
wp_wpmf_verifications    -- Identity verification requests
wp_wpmf_interests        -- Available interests/hobbies
wp_wpmf_interest_map     -- User-to-interest mapping
```

### Why Custom Tables?
- **Performance:** Optimized queries for dating-specific data
- **Scalability:** Better handling of large user bases
- **Flexibility:** Dating-specific indexing and relationships
- **Clean Separation:** No pollution of WordPress core tables

## Internationalization

### Translation Support
- **Text Domain:** `wpmatch-free`
- **POT File:** `languages/wpmatch-free.pot`
- **Strings:** 160+ translatable strings
- **Standards:** Full WordPress i18n compliance

### Available Translations
- English (en_US) - Built-in
- Contribute translations at [WordPress.org](https://translate.wordpress.org/)

### For Translators
1. Download the POT file from `/languages/wpmatch-free.pot`
2. Use tools like Poedit to create language-specific PO files
3. Save MO files in the `/languages/` directory
4. Follow WordPress naming convention: `wpmatch-free-{locale}.mo`

## Developer Documentation

### Hooks & Filters

#### Actions
```php
// Profile creation/update
do_action( 'wpmf_profile_created', $profile_id, $user_id );
do_action( 'wpmf_profile_updated', $profile_id, $user_id, $old_data, $new_data );

// Messaging system
do_action( 'wpmf_message_sent', $message_id, $sender_id, $recipient_id );
do_action( 'wpmf_like_added', $like_id, $actor_id, $target_user_id );

// Moderation events
do_action( 'wpmf_photo_approved', $photo_id, $user_id );
do_action( 'wpmf_user_reported', $report_id, $reporter_id, $target_id );
```

#### Filters
```php
// Customize search results
$profiles = apply_filters( 'wpmf_search_results', $profiles, $filters );

// Modify rate limits
$limit = apply_filters( 'wpmf_messages_per_day', 20, $user_id );
$limit = apply_filters( 'wpmf_likes_per_day', 50, $user_id );

// Content filtering
$message = apply_filters( 'wpmf_message_content', $message, $sender_id );
```

### Custom Development

#### Adding Custom Profile Fields
```php
// Add field to profile form
add_action( 'wpmf_profile_form_fields', 'my_custom_field' );
function my_custom_field( $profile ) {
    echo '<label>Custom Field: <input name="custom_field" value="' . 
         esc_attr( $profile['custom_field'] ?? '' ) . '"></label>';
}

// Save custom field data
add_action( 'wpmf_profile_save', 'save_custom_field', 10, 2 );
function save_custom_field( $profile_id, $data ) {
    if ( isset( $data['custom_field'] ) ) {
        wpmf_profile_meta_update( $profile_id, 'custom_field', $data['custom_field'] );
    }
}
```

#### Extending the REST API
```php
// Add custom endpoint
add_action( 'rest_api_init', 'register_custom_endpoint' );
function register_custom_endpoint() {
    register_rest_route( 'wpmatch-free/v1', '/custom', array(
        'methods' => 'GET',
        'callback' => 'handle_custom_endpoint',
        'permission_callback' => '__return_true',
    ));
}
```

### Testing

#### Running Tests
```bash
# Install WordPress test suite
composer run test:install

# Run all tests
composer run test

# Run specific test class
vendor/bin/phpunit tests/ProfilesTest.php

# Generate coverage report
composer run test:coverage
```

#### Writing Tests
```php
class MyCustomTest extends WP_UnitTestCase {
    public function test_custom_functionality() {
        $user_id = $this->factory->user->create();
        $result = my_custom_function( $user_id );
        $this->assertTrue( $result );
    }
}
```

## Performance Considerations

### Caching Strategy
- Profile data caching with WordPress object cache
- Search results caching for common queries
- Photo thumbnail caching
- Rate limit data stored in transients

### Database Optimization
- Strategic indexing on frequently queried columns
- Efficient JOIN operations for complex searches  
- Pagination for large result sets
- Regular cleanup of old data (messages, logs)

### Scalability Tips
- Use a caching plugin (Redis, Memcached)
- Consider CDN for photo serving
- Regular database maintenance
- Monitor slow query log

## Security

### Input Validation
- All user inputs sanitized and validated
- SQL injection prevention with prepared statements
- XSS protection through proper escaping
- File upload restrictions and validation

### Access Control
- Capability-based permission system
- Nonce verification for form submissions
- Rate limiting to prevent abuse
- Blocking system for user safety

### Best Practices
- Regular security updates
- Strong password requirements
- Two-factor authentication compatibility
- Security monitoring and logging

## Troubleshooting

### Common Issues

#### "Database tables not created"
**Solution:**
1. Deactivate and reactivate the plugin
2. Check MySQL user permissions
3. Verify PHP error logs for database errors

#### "Search results not showing"
**Solution:**
1. Check if users have completed profiles
2. Verify privacy settings
3. Ensure no blocking relationships exist

#### "Rate limits too restrictive"
**Solution:**
1. Go to WP Match > Settings > Rate Limits
2. Adjust daily limits as needed
3. Consider user roles and capabilities

### Debug Mode
Enable WordPress debug mode for detailed error information:
```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Support Channels
- **Documentation:** This README and inline code comments
- **Issues:** GitHub repository issues
- **Community:** WordPress.org plugin support forum

## Contributing

### Development Setup
```bash
# Clone repository
git clone https://github.com/your-repo/wpmatch-free.git
cd wpmatch-free

# Install dependencies
composer install

# Set up testing environment
composer run test:install

# Run coding standards check
composer run phpcs
```

### Code Standards
- Follow WordPress Coding Standards (WPCS)
- Use PHP_CodeSniffer with included configuration
- Write PHPUnit tests for new functionality
- Document all functions and classes

### Pull Request Process
1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Update documentation as needed
6. Submit pull request with clear description

## Changelog

### Version 0.1.0 (Initial Release)
- **Core Features**
  - Complete profile management system
  - Advanced search and discovery
  - Private messaging with rate limiting
  - Like system with match detection
  - Comprehensive moderation tools
  
- **Safety & Privacy**
  - GDPR compliance with WordPress privacy tools
  - User blocking and reporting system
  - Photo moderation workflow
  - Content filtering and word blacklist
  
- **Developer Features**
  - REST API with authentication
  - Gutenberg blocks for all shortcodes
  - Custom database architecture
  - Comprehensive test suite
  - Translation-ready with POT file
  
- **Admin Interface**
  - Settings page with all configurations
  - Photo moderation dashboard
  - User reports management
  - Verification workflow tools

## Roadmap

### Version 0.2.0 (Planned)
- Advanced matching algorithms
- Real-time messaging (WebSocket support)
- Mobile app integration
- Social media login integration
- Advanced photo management

### Version 0.3.0 (Planned)
- Event system for dating events
- Group dating features
- Video chat integration
- Advanced analytics dashboard
- Premium features framework

## Credits

### Core Team
- **Terry Arthur** - Lead Developer

### Contributors
- Community contributors welcome!

### Third-Party Libraries
- WordPress Core APIs
- WordPress Coding Standards (WPCS)
- PHPUnit for testing
- Yoast PHPUnit Polyfills

## License

This plugin is licensed under the GNU General Public License v3.0 or later.

```
WP Match Free - WordPress Dating Plugin
Copyright (C) 2025 Terry Arthur

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```

## Support

For support, bug reports, and feature requests:

- **WordPress.org:** [Plugin Support Forum](https://wordpress.org/support/plugin/wpmatch-free/)
- **GitHub:** [Issues Tracker](https://github.com/your-repo/wpmatch-free/issues)
- **Documentation:** [Developer Docs](https://github.com/your-repo/wpmatch-free/wiki)

---

**Made with ❤️ for the WordPress community**