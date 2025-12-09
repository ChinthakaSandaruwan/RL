// View All Rooms JavaScript

function updateSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    window.location.href = url.toString();
}

// Auto-submit on sort change is handled by onchange in HTML
// Additional functionality can be added here as needed
