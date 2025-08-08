# WP Match Free - Demo Content System

## Overview

The WP Match Free plugin includes a comprehensive demo content management system that allows administrators to create realistic demo users with complete profiles. This helps with testing site functionality, showcasing designs, and demonstrating features to clients.

## Features

### Free Version
- **20 Demo Users**: Create up to 20 realistic demo users for free
- **Complete Profiles**: Each demo user includes detailed profile information
- **Easy Management**: Simple admin interface for creating and managing demo content
- **Safe Cleanup**: Remove all demo users and data with one click
- **Realistic Data**: Authentic-looking names, ages, locations, and interests

### Demo User Details
Each demo user includes:
- Personal information (name, age, gender, location)
- Profile photos (coming soon)
- Complete biography and interests
- Occupation and education details
- Physical characteristics (height, body type, etc.)
- Lifestyle preferences (smoking, drinking, etc.)
- Relationship goals and status

### Admin Interface
Access demo management through **WP Admin → WP Match → Demo Content**

#### Statistics Dashboard
- Total demo users created
- Users with complete profiles  
- Remaining free slots
- Usage limits and restrictions

#### Demo Creation
- Choose number of users (5, 10, 15, or 20)
- One-click creation process
- Progress feedback and notifications
- Automatic profile field population

#### Cleanup Tools
- Remove all demo users instantly
- Clean up associated profile data
- Irreversible action with confirmation
- Complete data removal

## Premium Demo Packs

### Extended Demo Pack - $19.99
- 100 additional diverse demo users
- Enhanced profile photos
- Expanded demographics
- Custom interests and hobbies
- Professional profiles

### Professional Demo Pack - $49.99  
- 500 comprehensive demo users
- Demo messages and conversations
- Simulated user interactions
- Advanced demographics
- Industry-specific profiles

### Enterprise Demo Pack - $99.99
- Unlimited demo user generation
- Custom profile templates
- Bulk import/export tools
- Advanced customization options
- Priority support

## Technical Implementation

### Database Structure
Demo users are stored in WordPress's standard user tables with additional profile data in custom tables:
- `wp_wpmatch_profile_fields` - Field definitions
- `wp_wpmatch_profile_values` - User profile data

### Demo User Identification
- All demo users have usernames prefixed with `wpmatch_demo_`
- Special user meta `wpmatch_is_demo_user` = true
- Creation timestamp stored for tracking

### Profile Field Integration
- Automatically populates all configured profile fields
- Maps common field types (age, gender, location, etc.)
- Generates appropriate values based on field type
- Supports custom field configurations

### AJAX Operations
- Asynchronous creation and cleanup
- Real-time progress updates
- Error handling and notifications
- Secure nonce verification

## Usage Examples

### For Site Testing
1. Create 10-15 demo users
2. Test search functionality with various criteria
3. Verify profile display layouts
4. Test messaging and interaction features

### For Client Demonstrations
1. Generate full set of 20 demo users
2. Show complete user browsing experience
3. Demonstrate search and filtering
4. Showcase profile completeness and design

### For Development
1. Use demo users for feature development
2. Test edge cases with varied profile data
3. Verify responsive design across different content lengths
4. Performance testing with realistic data volumes

## Best Practices

### Demo Content Management
- Create demo users before client presentations
- Clean up demo content before going live
- Use demo content for testing, not production
- Regular cleanup during development phases

### Profile Field Setup
Ensure you have configured profile fields before creating demo users:
1. Go to **WP Match → Profile Fields**
2. Set up basic fields (age, gender, location, bio)
3. Configure additional fields as needed
4. Then create demo users for full profiles

### Security Considerations
- Demo users cannot log in (random passwords)
- All demo content is clearly marked
- Easy identification and removal
- No personal or sensitive real data

## Addon Development

### Creating Demo Content Addons
```php
// Register addon with framework
WPMatch_Addon_Framework::register_addon(array(
    'id' => 'my_demo_pack',
    'name' => 'My Demo Pack',
    'demo_users_limit' => 100,
    // ... other config
));

// Add additional profiles
add_filter('wpmatch_demo_profiles', function($profiles) {
    return array_merge($profiles, $my_additional_profiles);
});
```

### Licensing Integration
- Addons can require license validation
- Framework handles license checking
- Graceful degradation without license
- Integration with external license servers

## Troubleshooting

### Common Issues

**Demo users not appearing in search:**
- Ensure profile fields are configured
- Check that demo users have profile data populated
- Verify search criteria include demo user data

**Cannot create more demo users:**
- Free limit is 20 users maximum
- Clean up existing demo users first
- Consider premium demo packs for higher limits

**Demo cleanup not working:**
- Check admin permissions
- Verify AJAX requests are working
- Look for JavaScript console errors
- Ensure proper nonce verification

### Support and Customization
For technical support or custom demo content requirements, contact the WP Match development team or consider the Enterprise Demo Pack for advanced customization options.