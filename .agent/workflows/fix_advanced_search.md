---
description: Fix Advanced Search Logic and Filters
---

# Fix Advanced Search Logic and Filters

The advanced search functionality was broken due to unreachable code in the results handler and missing filter logic in the listing pages. This workflow documents the fixes applied.

## 1. Fix `advanced_result.php` Logic

The file `public/search/advanced_result.php` had the following issues:
- Code for 'Room' and 'Vehicle' categories was unreachable (inside the 'Property' block after an `exit`).
- Redirects were pointing to non-existent `_search.php` files.
- Parameters were not correctly mapped to what the listing pages expected.

**Action:** Rewrote `advanced_result.php` to:
- Use correct `if/elseif` structure.
- Map form parameters (e.g., `prop_beds`) to view parameters (e.g., `min_bedrooms`).
- Redirect to the correct `view_all.php` pages (e.g., `public/property/view_all/view_all.php`).

## 2. Update Property Listing Logic

The file `public/property/view_all/view_all.php` functionality was enhanced to support new filters sent by the advanced search.

**Action:** Added logic to handle:
- Location (Province, District, City)
- Square Footage (Min/Max)
- Amenities (filtering properties that have all selected amenities)

## 3. Update Room Listing Logic

The file `public/room/view_all/view_all.php` was similarly updated.

**Action:** Added logic to handle:
- Location (Province, District, City)
- Guests Count
- Amenities

## 4. Update Vehicle Listing Logic

The file `public/vehicle/view_all/view_all.php` was similarly updated.

**Action:** Added logic to handle:
- Location (Province, District, City)
- Brand
- Seats
- Driver Availability

## Verification

To verify the fix:
1. Open the website and click on "Advanced Search".
2. Select "Property" and filter by a specific criteria (e.g., District, Amenities). Searching should take you to the Property Listing page with correctly filtered results.
3. Select "Room" or "Vehicle" and perform a search. It should now correctly redirect to the respective listing page instead of the homepage, and the filters should be applied.
