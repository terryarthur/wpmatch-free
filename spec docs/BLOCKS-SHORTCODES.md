# Blocks & Shortcodes Documentation

**Technical implementation guide for WP Match Free blocks and shortcodes**

## Overview

WP Match Free provides both shortcodes and Gutenberg blocks for displaying dating functionality on your WordPress site. All blocks are built on top of shortcodes, ensuring consistency and backward compatibility.

## Architecture

### Shortcode Foundation
All functionality is first implemented as shortcodes in `includes/shortcodes.php`, then wrapped by Gutenberg blocks in `includes/blocks.php`. This approach provides:

- **Backward compatibility** with older WordPress versions
- **Theme compatibility** regardless of block support
- **Consistent functionality** across rendering methods
- **Easy debugging** with a single code path

### Block Registration
Blocks are registered server-side using `register_block_type()` with render callbacks that invoke the corresponding shortcodes:

```php
register_block_type( 'wpmf/profile-edit', array( 
    'render_callback' => 'wpmf_block_profile_edit' 
) );
```

## Shortcodes

### `[wpmf_profile_edit]`

**Purpose:** Displays a profile editing form for logged-in users.

#### Implementation Details

**File:** `includes/shortcodes.php:5-24`

**Function:** `wpmf_sc_profile_edit()`

**Authentication Check:**
```php
if ( ! is_user_logged_in() ) { 
    return esc_html__( 'Please log in.', 'wpmatch-free' ); 
}
```

#### Form Fields

| Field | Type | Validation | Purpose |
|-------|------|------------|---------|
| `gender` | text input | `esc_attr()` | User's gender identity |
| `region` | text input | `esc_attr()` | Geographic location |
| `headline` | text input | `esc_attr()` | Profile headline/tagline |
| `bio` | textarea | `esc_textarea()` | Extended biography |

#### Security Features

1. **Nonce Verification:** `wp_create_nonce( 'wpmf_profile_save' )`
2. **Input Sanitization:** All fields properly escaped for output
3. **CSRF Protection:** Form submission requires valid nonce

#### Form Processing

**Handler:** `wpmf_handle_profile_edit_post()` (lines 27-44)

**Hook:** `template_redirect` - Processes before headers are sent

**Data Flow:**
1. Verify user authentication
2. Validate nonce token
3. Sanitize input data
4. Check for existing profile
5. Update existing or create new profile

**Sanitization Methods:**
- `sanitize_text_field()` - For simple text inputs
- `wp_kses_post()` - For bio field (allows basic HTML)

#### Database Operations

**Profile Lookup:** `wpmf_profile_get_by_user_id( $user_id )`

**Profile Update:** `wpmf_profile_update_by_user_id( $user_id, $data )`

**Profile Creation:** `wpmf_profile_create( $data )`

#### CSS Classes

- `.wpmf-profile-edit` - Form container
- Form follows WordPress admin styling conventions

---

### `[wpmf_search_form]`

**Purpose:** Displays search form with dating-specific filters.

#### Implementation Details

**File:** `includes/shortcodes.php:47-57`

**Function:** `wpmf_sc_search_form()`

**Method:** GET - Uses URL parameters for search persistence

#### Form Fields

| Field | Type | Parameter | Purpose |
|-------|------|-----------|---------|
| Region | text input | `region` | Geographic filter |
| Age Min | number input | `age_min` | Minimum age filter |
| Age Max | number input | `age_max` | Maximum age filter |
| Has Photo | checkbox | `has_photo` | Require profile photo |

#### Input Handling

**Preservation:** Form values are preserved from `$_GET` parameters:
```php
value="' . esc_attr( $_GET['region'] ?? '' ) . '"
```

**Checkbox State:** Uses WordPress `checked()` helper:
```php
checked( ! empty( $_GET['has_photo'] ), true, false )
```

#### Security Considerations

- **Output Escaping:** All dynamic values use `esc_attr()`
- **No Nonce Required:** Read-only search form
- **Parameter Validation:** Handled by results processing

#### CSS Classes

- `.wpmf-search-form` - Form container
- Standard form styling applied

---

### `[wpmf_search_results]`

**Purpose:** Displays search results based on current GET parameters.

#### Implementation Details

**File:** `includes/shortcodes.php:59-86`

**Function:** `wpmf_sc_search_results()`

**Database Table:** `wp_wpmf_profiles`

#### Query Building

**Base Query:**
```sql
SELECT * FROM {$table} WHERE status='active' 
ORDER BY last_active DESC LIMIT 50
```

**Dynamic Filters:** Added based on GET parameters

| Parameter | SQL Condition | Sanitization |
|-----------|---------------|--------------|
| `region` | `region = %s` | `sanitize_text_field()` |
| `age_min` | `age >= %d` | `absint()` |
| `age_max` | `age <= %d` | `absint()` |

#### Security Features

1. **Prepared Statements:** All dynamic queries use `$wpdb->prepare()`
2. **Input Sanitization:** Parameters sanitized before query
3. **Output Escaping:** Results escaped with `esc_html()`

#### Result Rendering

**Container:** `.wpmf-search-results`

**Individual Cards:** `.wpmf-card`
- `.wpmf-card-headline` - User's headline
- `.wpmf-card-region` - User's location

#### Performance Considerations

- **Result Limit:** Hardcoded 50 result maximum
- **Indexing:** Database indexed on `last_active` for sorting
- **No Caching:** Direct database query (consider caching for high-traffic sites)

#### Access Control Integration

Results respect user blocking relationships (implemented in REST API, not shortcode for performance).

---

## Gutenberg Blocks

### Block Registration

**File:** `includes/blocks.php`

**Hook:** `init` action

**Registration Function:** `wpmf_register_blocks()`

### Block Implementations

#### Profile Edit Block (`wpmf/profile-edit`)

**Render Callback:** `wpmf_block_profile_edit()`

```php
function wpmf_block_profile_edit( $attrs = array(), $content = '' ) {
    return do_shortcode( '[wpmf_profile_edit]' );
}
```

**Parameters:**
- `$attrs` - Block attributes (currently unused)
- `$content` - Block inner content (currently unused)

#### Search Form Block (`wpmf/search-form`)

**Render Callback:** `wpmf_block_search_form()`

```php
function wpmf_block_search_form( $attrs = array(), $content = '' ) {
    return do_shortcode( '[wpmf_search_form]' );
}
```

#### Search Results Block (`wpmf/search-results`)

**Render Callback:** `wpmf_block_search_results()`

```php
function wpmf_block_search_results( $attrs = array(), $content = '' ) {
    return do_shortcode( '[wpmf_search_results]' );
}
```

### Block Assets

**JavaScript:** `assets/blocks.js` (referenced but not yet created)

**CSS:** `assets/blocks.css` (referenced but not yet created)

**Dependencies:**
- `wp-blocks` - WordPress block library
- `wp-element` - React-like elements
- `wp-editor` - Block editor components

**Registration:** `wpmf_blocks_assets()` function

```php
wp_register_script( 'wpmf-blocks', 
    plugins_url( 'assets/blocks.js', __DIR__ . '/../wpmatch-free.php' ), 
    array( 'wp-blocks', 'wp-element', 'wp-editor' ), 
    WPMATCH_FREE_VERSION, 
    true 
);
```

### Block Editor Integration

**Category:** Blocks should appear under "Dating" category

**Icons:** Consider using Dashicons or custom SVG icons

**Example Block Registration (Full):**
```php
register_block_type( 'wpmf/profile-edit', array(
    'title' => __( 'Dating Profile Editor', 'wpmatch-free' ),
    'description' => __( 'Allow users to edit their dating profile', 'wpmatch-free' ),
    'category' => 'dating',
    'icon' => 'admin-users',
    'keywords' => array( 'dating', 'profile', 'user' ),
    'render_callback' => 'wpmf_block_profile_edit',
    'attributes' => array(
        // Future: Add block-specific attributes
    ),
) );
```

## Asset Creation (Future Enhancement)

### JavaScript Architecture

**Recommended Structure:**
```javascript
// assets/blocks.js
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

registerBlockType( 'wpmf/profile-edit', {
    title: __( 'Dating Profile Editor', 'wpmatch-free' ),
    icon: 'admin-users',
    category: 'dating',
    
    edit: function( props ) {
        return React.createElement( 'div', {
            className: 'wpmf-block-placeholder'
        }, __( 'Dating Profile Editor - Frontend Only', 'wpmatch-free' ) );
    },
    
    save: function() {
        return null; // Server-side rendering
    }
} );
```

### CSS Architecture

**Recommended Structure:**
```css
/* assets/blocks.css */

/* Block editor styling */
.wpmf-block-placeholder {
    padding: 20px;
    border: 2px dashed #ccc;
    text-align: center;
    background: #f9f9f9;
}

/* Frontend form styling */
.wpmf-profile-edit {
    max-width: 600px;
    margin: 0 auto;
}

.wpmf-profile-edit label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
}

.wpmf-profile-edit input,
.wpmf-profile-edit textarea {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Search form styling */
.wpmf-search-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.wpmf-search-form input {
    margin-bottom: 10px;
}

/* Results styling */
.wpmf-search-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.wpmf-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wpmf-card-headline {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.wpmf-card-region {
    color: #666;
    font-style: italic;
}
```

## Form Processing Deep Dive

### Profile Edit Processing

**Hook Priority:** `template_redirect` - Executes early in request lifecycle

**Processing Steps:**
1. **Authentication Check:**
   ```php
   if ( ! is_user_logged_in() ) { return; }
   ```

2. **Nonce Verification:**
   ```php
   if ( empty( $_POST['wpmf_nonce'] ) || 
        ! wp_verify_nonce( $_POST['wpmf_nonce'], 'wpmf_profile_save' ) ) { 
       return; 
   }
   ```

3. **Data Sanitization:**
   ```php
   $data = array(
       'gender' => sanitize_text_field( $_POST['gender'] ?? '' ),
       'region' => sanitize_text_field( $_POST['region'] ?? '' ),
       'headline' => sanitize_text_field( $_POST['headline'] ?? '' ),
       'bio' => wp_kses_post( $_POST['bio'] ?? '' ),
   );
   ```

4. **Database Operation:**
   ```php
   $exists = wpmf_profile_get_by_user_id( $user_id );
   if ( $exists ) {
       wpmf_profile_update_by_user_id( $user_id, $data );
   } else {
       $data['user_id'] = $user_id;
       wpmf_profile_create( $data );
   }
   ```

### Error Handling

**Current State:** Silent failure (no user feedback)

**Recommended Enhancement:**
```php
// Add success/error messages
add_action( 'wp_head', function() {
    if ( isset( $_GET['profile_updated'] ) ) {
        echo '<div class="notice notice-success"><p>' . 
             esc_html__( 'Profile updated successfully!', 'wpmatch-free' ) . 
             '</p></div>';
    }
});
```

## Integration Points

### WordPress Integration

**User System:** Integrates with WordPress user accounts

**Capabilities:** Uses custom capabilities for access control

**Hooks:** Follows WordPress action/filter patterns

**Standards:** Adheres to WordPress Coding Standards

### Theme Compatibility

**CSS Reset:** Inherits theme styling by default

**Responsive Design:** Uses flexible CSS patterns

**Accessibility:** Semantic HTML structure

**RTL Support:** Text direction handled by WordPress

### Plugin Compatibility

**Conflicts:** Minimal global scope pollution

**Hooks:** Standard WordPress hooks only

**Database:** Custom tables avoid conflicts

**Caching:** Compatible with object caching

## Performance Optimization

### Current Performance Profile

**Database Queries:**
- Profile Edit: 2-3 queries (lookup + update/insert)
- Search Form: 0 queries (static HTML)
- Search Results: 1 query (with prepared statement)

**Optimization Opportunities:**

1. **Caching Layer:**
   ```php
   // Cache search results
   $cache_key = 'wpmf_search_' . md5( serialize( $_GET ) );
   $results = wp_cache_get( $cache_key, 'wpmatch-free' );
   if ( false === $results ) {
       $results = $wpdb->get_results( $sql, ARRAY_A );
       wp_cache_set( $cache_key, $results, 'wpmatch-free', 300 ); // 5 minutes
   }
   ```

2. **Query Optimization:**
   ```php
   // Add composite indexes
   ALTER TABLE wp_wpmf_profiles 
   ADD INDEX search_idx (status, region, age, last_active);
   ```

3. **Pagination:**
   ```php
   // Add pagination to search results
   $page = max( 1, absint( $_GET['page'] ?? 1 ) );
   $per_page = 20;
   $offset = ( $page - 1 ) * $per_page;
   ```

### Memory Usage

**Current:** ~1-2MB per request (typical WordPress plugin range)

**Optimization:** Avoid loading unnecessary data in search results

## Security Considerations

### Input Validation

**OWASP Compliance:** All inputs validated and sanitized

**XSS Prevention:** Output escaping on all dynamic content

**CSRF Protection:** Nonce verification on state-changing operations

**SQL Injection:** Prepared statements for all database queries

### Access Control

**Authentication:** Required for profile editing

**Authorization:** Capability-based permissions

**Data Access:** Users can only edit their own profiles

**Privacy:** Search results respect blocking relationships

### Best Practices

1. **Principle of Least Privilege:** Minimal required capabilities
2. **Defense in Depth:** Multiple validation layers
3. **Fail Securely:** Graceful handling of errors
4. **Audit Trail:** Log important operations (future enhancement)

## Testing Strategy

### Unit Tests

**Profile Form Processing:**
```php
public function test_profile_form_processing() {
    $user_id = $this->factory->user->create();
    wp_set_current_user( $user_id );
    
    $_POST = array(
        'wpmf_nonce' => wp_create_nonce( 'wpmf_profile_save' ),
        'gender' => 'non-binary',
        'region' => 'test-region',
        'headline' => 'Test headline',
        'bio' => 'Test biography',
    );
    
    wpmf_handle_profile_edit_post();
    
    $profile = wpmf_profile_get_by_user_id( $user_id );
    $this->assertEquals( 'non-binary', $profile['gender'] );
}
```

**Search Results:**
```php
public function test_search_results_filtering() {
    // Create test profiles with different ages/regions
    $profiles = $this->create_test_profiles();
    
    $_GET = array( 'age_min' => 25, 'age_max' => 35 );
    
    ob_start();
    echo wpmf_sc_search_results();
    $output = ob_get_clean();
    
    // Assert correct profiles are displayed
    $this->assertStringContains( 'profile-within-range', $output );
    $this->assertStringNotContains( 'profile-outside-range', $output );
}
```

### Integration Tests

**Full Page Rendering:**
```php
public function test_full_page_with_shortcodes() {
    $post_id = $this->factory->post->create( array(
        'post_content' => '[wpmf_search_form][wpmf_search_results]'
    ) );
    
    $this->go_to( get_permalink( $post_id ) );
    
    // Assert both shortcodes render correctly
    $this->assertQueryTrue( 'is_single' );
    // Additional assertions for rendered content
}
```

## Migration & Upgrade Path

### Version Compatibility

**Shortcode Stability:** Shortcode APIs remain stable across versions

**Block Evolution:** Blocks may gain attributes in future versions

**Database Schema:** Migrations handled via activation hook

### Backward Compatibility

**Deprecated Features:** Maintain for at least 2 major versions

**Legacy Support:** Graceful degradation for older themes

**API Changes:** Follow semantic versioning principles

## Future Enhancements

### Block Attributes

**Planned Attributes:**
- `show_fields` - Array of fields to display in profile editor
- `max_results` - Limit search results per page
- `default_filters` - Pre-populate search form

### Advanced Features

**Real-time Updates:** WebSocket integration for live search

**Geolocation:** Browser-based location detection

**Advanced Matching:** Algorithm-based result ranking

**A/B Testing:** Multiple form layouts and styling options

---

**This documentation covers the technical implementation of all blocks and shortcodes. For user-facing documentation, see the main README.md file.**