<?php

return [
    // Page titles and headers
    'title' => 'Game Locations',
    'management' => 'Game Management',
    'game_management' => 'Game Management',
    'create' => 'Create Game Location',
    'edit' => 'Edit Game Location',
    'view' => 'View Game Location',
    
    // Actions
    'add_location' => 'Add Game Location',
    'save_location' => 'Save Location',
    'update_location' => 'Update Location',
    'delete_location' => 'Delete Location',
    'view_details' => 'View Details',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'bulk_activate' => 'Bulk Activate',
    'bulk_deactivate' => 'Bulk Deactivate',
    'bulk_delete' => 'Bulk Delete',
    
    // Form fields
    'name' => 'Game Name',
    'name_placeholder' => 'Enter unique game location name',
    'description' => 'Description',
    'description_placeholder' => 'Describe the location and what players can expect (minimum 10 characters)',
    'what_to_do' => 'What to do',
    'what_to_do_placeholder' => 'Provide clear instructions for players on what they need to do at this location (minimum 10 characters)',
    'google_maps_url' => 'Google Maps Embed URL',
    'google_maps_url_placeholder' => 'Paste Google Maps embed URL or iframe code',
    'latitude' => 'Latitude',
    'longitude' => 'Longitude',
    'coordinate_x' => 'Map X Coordinate',
    'coordinate_y' => 'Map Y Coordinate',
    'radius' => 'Radius (meters)',
    'quest_points' => 'Quest Points',
    'max_check_ins' => 'Max Check-ins per User',
    'regular_image' => 'Regular Image',
    'map_image' => 'Map Image',
    'is_active' => 'Active',
    
    // Table headers
    'game' => 'Game',
    'images' => 'Images',
    'coordinates' => 'Coordinates',
    'points' => 'Points',
    'status' => 'Status',
    'actions' => 'Actions',
    
    // Status
    'active' => 'Active',
    'inactive' => 'Inactive',
    'regular' => 'Regular',
    'map' => 'Map',
    
    // Coordinates
    'gps_coordinates' => 'GPS Coordinates',
    'map_coordinates' => 'Map Coordinates',
    'extract' => 'Extract',
    'extract_coordinates' => 'Extract Coordinates',
    
    // Messages
    'no_locations_found' => 'No game locations found',
    'create_first_location' => 'Create your first game location to get started.',
    'location_created' => 'Game location created successfully!',
    'location_updated' => 'Game location updated successfully!',
    'location_deleted' => 'Game location deleted successfully!',
    'locations_activated' => ':count game location(s) activated successfully!',
    'locations_deactivated' => ':count game location(s) deactivated successfully!',
    'locations_deleted' => ':count game location(s) deleted successfully!',
    'select_at_least_one' => 'Please select at least one game location.',
    'selected_count' => ':count game location(s) selected',
    
    // Image management
    'regular_image_removed' => 'Regular image removed successfully!',
    'map_image_removed' => 'Map image removed successfully!',
    'regular_image_marked_for_removal' => 'Regular image marked for removal (will be deleted when you save)',
    'map_image_marked_for_removal' => 'Map image marked for removal (will be deleted when you save)',
    'regular_image_removal_cancelled' => 'Regular image removal cancelled',
    'map_image_removal_cancelled' => 'Map image removal cancelled',
    'remove' => 'Remove',
    'undo_remove' => 'Undo Remove',
    'current_image' => 'Current Image',
    'current_map' => 'Current Map',
    
    // Coordinate extraction
    'coordinates_extracted' => 'Coordinates extracted successfully: :coordinates',
    'coordinates_extract_error' => 'Could not extract coordinates from the URL. Please check the URL format or enter coordinates manually.',
    'enter_url_first' => 'Please enter a Google Maps URL first.',
    'invalid_url_format' => 'Invalid URL format.',
    'coordinates_out_of_range' => 'Extracted coordinates are out of valid range.',
    'url_processing_error' => 'Error processing URL: :error',
    
    // Search and filter
    'search_locations' => 'Search locations...',
    'all_status' => 'All Status',
    'filter_active' => 'Active',
    'filter_inactive' => 'Inactive',
    
    // Interactive map
    'interactive_map' => 'Interactive Map',
    'interactive_positioning' => 'Interactive Positioning',
    'interactive_map_controls' => 'Interactive Map Controls',
    'no_map_available' => 'No interactive map available',
    'map_instructions_1' => 'Click and drag to pan • Scroll to zoom • Double-click to zoom',
    'map_instructions_2' => 'Enter fullscreen for enhanced controls with zoom indicator',
    'map_instructions_3' => 'Keyboard: Space = fullscreen, + = zoom in, - = zoom out, R = reset',
    'zoom_in' => 'Zoom In',
    'zoom_out' => 'Zoom Out',
    'reset_view' => 'Reset View',
    'toggle_fullscreen' => 'Toggle Fullscreen',
    
    // Location details
    'location_information' => 'Location Information',
    'gps_label' => 'GPS',
    'map_coordinates_label' => 'Map Coordinates',
    'quest_points_label' => 'Quest Points',
    'checkin_radius' => 'check-in radius',
    'open_in_google_maps' => 'Open in Google Maps',
    'close_details' => 'Close Details',
    
    // Validation messages
    'name_required' => 'Game name is required.',
    'name_unique' => 'A game location with this name already exists.',
    'description_min' => 'Description must be at least 10 characters.',
    'what_to_do_min' => 'What to do must be at least 10 characters.',
    'latitude_required' => 'Latitude is required.',
    'longitude_required' => 'Longitude is required.',
    'latitude_between' => 'Latitude must be between -90 and 90.',
    'longitude_between' => 'Longitude must be between -180 and 180.',
    'radius_required' => 'Radius is required.',
    'radius_min' => 'Radius must be at least 10 meters.',
    'radius_max' => 'Radius cannot exceed 1000 meters.',
    'quest_points_required' => 'Quest points is required.',
    'quest_points_min' => 'Quest points cannot be negative.',
    'quest_points_max' => 'Quest points cannot exceed 1000.',
    'image_max' => 'Regular image may not be greater than 2MB.',
    'map_image_max' => 'Map image may not be greater than 5MB.',
    'image_mimes' => 'Image must be a JPEG, PNG, JPG, or WebP file.',
    'coordinate_positive' => 'Coordinate must be positive.',
    'max_check_ins_min' => 'Maximum check-ins must be at least 1.',
    'max_check_ins_max' => 'Maximum check-ins cannot exceed 100.',
    
    // Error messages
    'upload_failed' => 'Failed to upload image: :error',
    'save_failed' => 'Failed to save game location: :error',
    'delete_failed' => 'Failed to delete game location: :error',
    'remove_image_failed' => 'Failed to remove image: :error',
    'activate_failed' => 'Failed to activate game locations: :error',
    'deactivate_failed' => 'Failed to deactivate game locations: :error',
    'bulk_delete_failed' => 'Failed to delete game locations: :error',
    
    // Confirmation messages
    'confirm_delete' => 'Are you sure you want to delete this game location?',
    'confirm_bulk_delete' => 'Are you sure you want to delete the selected game locations?',
    
    // Additional user-facing messages
    'explore_outdoor_locations' => 'Explore outdoor locations with interactive maps and detailed information.',
    'admin_will_create_locations' => 'Game locations will appear here when they are created by administrators.',
    'outdoor_game_locations' => 'Outdoor Game Locations',
];