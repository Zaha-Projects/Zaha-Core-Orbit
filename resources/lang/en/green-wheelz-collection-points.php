<?php

return [
    'title' => 'Collection points',
    'subtitle' => 'Manage collection points, their types, and link them to Zaha branches.',
    'actions' => [
        'create' => 'Add collection point',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'update' => 'Update',
        'back' => 'Back',
        'search' => 'Search',
    ],
    'fields' => [
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'branch' => 'Linked Zaha branch',
        'type' => 'Collection point type',
        'status' => 'Status',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'location_search' => 'Search for a location',
        'google_maps_url' => 'Google Maps URL (optional)',
    ],
    'types' => [
        'minor' => 'Minor collection point',
        'major' => 'Major collection point',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'placeholders' => [
        'select_branch' => 'No branch selected',
        'location_search' => 'Search for a school, institution, or location name',
        'google_maps_url' => 'Paste a Google Maps link',
    ],
    'hints' => [
        'location_optional' => 'Location is optional; you can save without selecting one.',
    ],
    'messages' => [
        'created' => 'Collection point created successfully.',
        'updated' => 'Collection point updated successfully.',
        'deleted' => 'Collection point deleted successfully.',
    ],
    'alerts' => [
        'verify_location' => 'Please confirm the collection point location on the map before saving.',
    ],
    'errors' => [
        'google_maps_url' => 'No coordinates were found in the link. Please check the URL.',
    ],
    'confirm_delete' => 'Are you sure you want to delete this collection point?',
    'empty' => 'No collection points found.',
];
