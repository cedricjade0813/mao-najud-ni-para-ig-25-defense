<?php
include '../includes/db_connect.php';
include '../includes/header.php';
// Connect to DB and fetch medicines
try {
    
    
    // Get year filter parameter
    $filterYear = isset($_GET['year']) ? $_GET['year'] : '';
    
    // Build query with year filtering
    $query = 'SELECT * FROM medicines';
    $params = [];
    
    if ($filterYear) {
        $query .= ' WHERE YEAR(created_at) = ? OR YEAR(expiry) = ?';
        $params = [$filterYear, $filterYear];
    }
    
    $query .= ' ORDER BY name ASC';
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>

<style>
    html,
    body {
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* Internet Explorer 10+ */
    }

  html::-webkit-scrollbar,
  body::-webkit-scrollbar {
        display: none;
        /* Safari and Chrome */
  }
</style>


<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Medicine Inventory</h1>
        <p class="text-gray-600">Manage and track your medicine inventory status.</p>
    </div>

    <!-- Available Medicines Section -->
    <div id="availableSection" class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- Section Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Available Medicines</h3>
                    <p class="text-gray-600 text-sm mt-1">Medicines currently in stock and ready for dispensing.</p>
                </div>
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="availableSearch" class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medicines...">
                </div>
        </div>
    </div>
    
        <!-- Status Tabs -->
        <div class="px-6 py-3 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button type="button" class="medicine-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="available">
                    Available (<span id="availableCount"><?= count(array_filter($medicines, function($med) { return $med['expiry'] >= date('Y-m-d') && $med['quantity'] > 0; })) ?></span>)
                </button>
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="outOfStock">
                    Out of Stock (<span id="outOfStockCount"><?= count(array_filter($medicines, function($med) { return $med['quantity'] == 0; })) ?></span>)
                </button>
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="expired">
                    Expired (<span id="expiredCount"><?= count(array_filter($medicines, function($med) { return $med['expiry'] < date('Y-m-d'); })) ?></span>)
                </button>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Medicine Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Dosage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Expiry Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="availableTableBody">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600" id="availableRecordsInfo">
                    Loading...
                </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination" id="availablePaginationNav">
                    <!-- Pagination will be loaded via AJAX -->
                </nav>
            </div>
        </div>
    </div>
    <!-- Out of Stock Medicines Section -->
    <div id="outOfStockSection" class="bg-white rounded-xl shadow-sm border border-gray-200 hidden">
        <!-- Section Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Out of Stock Medicines</h3>
                    <p class="text-gray-600 text-sm mt-1">Medicines that need to be restocked.</p>
                </div>
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="outOfStockSearch" class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medicines...">
                </div>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="px-6 py-3 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="available">
                    Available (<span id="availableCount2"><?= count(array_filter($medicines, function($med) { return $med['expiry'] >= date('Y-m-d') && $med['quantity'] > 0; })) ?></span>)
                </button>
                <button type="button" class="medicine-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="outOfStock">
                    Out of Stock (<span id="outOfStockCount2"><?= count(array_filter($medicines, function($med) { return $med['quantity'] == 0; })) ?></span>)
                </button>
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="expired">
                    Expired (<span id="expiredCount2"><?= count(array_filter($medicines, function($med) { return $med['expiry'] < date('Y-m-d'); })) ?></span>)
                </button>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Medicine Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Dosage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Expiry Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="outOfStockTableBody">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600" id="outOfStockRecordsInfo">
                    Loading...
            </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination" id="outOfStockPaginationNav">
                    <!-- Pagination will be loaded via AJAX -->
                </nav>
            </div>
        </div>
    </div>

    <!-- Expired Medicines Section -->
    <div id="expiredSection" class="bg-white rounded-xl shadow-sm border border-gray-200 hidden">
        <!-- Section Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Expired Medicines</h3>
                    <p class="text-gray-600 text-sm mt-1">Medicines that have passed their expiry date and need disposal.</p>
                </div>
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="expiredSearch" class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medicines...">
                </div>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="px-6 py-3 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="available">
                    Available (<span id="availableCount3"><?= count(array_filter($medicines, function($med) { return $med['expiry'] >= date('Y-m-d') && $med['quantity'] > 0; })) ?></span>)
                    </button>
                <button type="button" class="medicine-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="outOfStock">
                    Out of Stock (<span id="outOfStockCount3"><?= count(array_filter($medicines, function($med) { return $med['quantity'] == 0; })) ?></span>)
                    </button>
                <button type="button" class="medicine-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="expired">
                    Expired (<span id="expiredCount3"><?= count(array_filter($medicines, function($med) { return $med['expiry'] < date('Y-m-d'); })) ?></span>)
                </button>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Medicine Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Dosage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Expiry Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="expiredTableBody">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600" id="expiredRecordsInfo">
                    Loading...
                </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination" id="expiredPaginationNav">
                    <!-- Pagination will be loaded via AJAX -->
                </nav>
            </div>
        </div>
    </div>
</main>

<script>
// Global variables for search terms
window.currentAvailableSearchTerm = '';
window.currentOutOfStockSearchTerm = '';
window.currentExpiredSearchTerm = '';

// Search functions for each medicine type - following inventory.php pattern
function performAvailableSearch(searchTerm, page = 1) {
    // Show loading state
    const availableTableBody = document.getElementById('availableTableBody');
    if (availableTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentAvailableSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentAvailableSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_available_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateAvailableTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_available_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateAvailableTable(data.medicines, data.pagination);
                    } else {
                console.error('Search error:', data.message);
                if (availableTableBody) {
                    availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (availableTableBody) {
                availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (availableTableBody) {
            availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

function performOutOfStockSearch(searchTerm, page = 1) {
    // Show loading state
    const outOfStockTableBody = document.getElementById('outOfStockTableBody');
    if (outOfStockTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentOutOfStockSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentOutOfStockSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_out_of_stock_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateOutOfStockTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_out_of_stock_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateOutOfStockTable(data.medicines, data.pagination);
            } else {
                console.error('Search error:', data.message);
                if (outOfStockTableBody) {
                    outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (outOfStockTableBody) {
                outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (outOfStockTableBody) {
            outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

function performExpiredSearch(searchTerm, page = 1) {
    // Show loading state
    const expiredTableBody = document.getElementById('expiredTableBody');
    if (expiredTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentExpiredSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentExpiredSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_expired_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateExpiredTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_expired_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateExpiredTable(data.medicines, data.pagination);
            } else {
                console.error('Search error:', data.message);
                if (expiredTableBody) {
                    expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (expiredTableBody) {
                expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (expiredTableBody) {
            expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

// Update table functions - following inventory.php pattern
function updateAvailableTable(medicines, pagination = null) {
    const availableTableBody = document.getElementById('availableTableBody');
    if (!availableTableBody) return;
    
    if (medicines.length === 0) {
        availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('availableRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when results are found
    const paginationContainer = document.getElementById('availableRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'flex';
        
        if (pagination) {
            const startRecord = pagination.start_record || 1;
            const endRecord = pagination.end_record || medicines.length;
            const infoText = document.getElementById('availableRecordsInfo');
            if (infoText) {
                infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
            }
            
            // Update pagination numbers based on search results
            updateAvailablePaginationNumbers(pagination);
        } else {
            // If no pagination data, replace the entire pagination container with simple info
            paginationContainer.innerHTML = `
                <div class="text-sm text-gray-600">
                    Showing 1 to ${medicines.length} of ${medicines.length} entries
        </div>
            `;
        }
    }
    
    let html = '';
    medicines.forEach(medicine => {
        html += `
            <tr class="hover:bg-gray-50 available-row" data-name="${medicine.name.toLowerCase()}" data-category="${medicine.dosage.toLowerCase()}">
                <td class="px-6 py-4 whitespace-nowrap w-1/3">
                    <div class="text-sm font-medium text-gray-900">${medicine.name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.dosage}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.formatted_expiry}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.quantity}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${medicine.status_class}">
                        ${medicine.status}
                    </span>
                </td>
            </tr>
        `;
    });
    
    availableTableBody.innerHTML = html;
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentAvailablePagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

function updateOutOfStockTable(medicines, pagination) {
    const tbody = document.getElementById('outOfStockTableBody');
    tbody.innerHTML = '';
    
    if (medicines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('outOfStockRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when there are results
    const paginationContainer = document.getElementById('outOfStockRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'block';
    }
    
    medicines.forEach(med => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 out-of-stock-row';
        row.setAttribute('data-name', med.name.toLowerCase());
        row.setAttribute('data-category', med.dosage.toLowerCase());
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap w-1/3">
                <div class="text-sm font-medium text-gray-900">${med.name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.dosage}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.formatted_expiry}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.quantity}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${med.status_class}">
                    ${med.status}
                </span>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateOutOfStockPagination(pagination);
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentOutOfStockPagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

function updateExpiredTable(medicines, pagination) {
    const tbody = document.getElementById('expiredTableBody');
    tbody.innerHTML = '';
    
    if (medicines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('expiredRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when there are results
    const paginationContainer = document.getElementById('expiredRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'block';
    }
    
    medicines.forEach(med => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 expired-row';
        row.setAttribute('data-name', med.name.toLowerCase());
        row.setAttribute('data-category', med.dosage.toLowerCase());
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap w-1/3">
                <div class="text-sm font-medium text-gray-900">${med.name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.dosage}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.formatted_expiry}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.quantity}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${med.status_class}">
                    ${med.status}
                </span>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateExpiredPagination(pagination);
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentExpiredPagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize with available section active
    document.getElementById('availableSection').classList.remove('hidden');
    document.getElementById('outOfStockSection').classList.add('hidden');
    document.getElementById('expiredSection').classList.add('hidden');
    
    // Load initial data for available medicines
    performAvailableSearch('', 1);
    
    // Load counts for other categories
    performOutOfStockSearch('', 1);
    performExpiredSearch('', 1);
});

// Handle medicine status filter tabs - using same logic as appointments.php
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.medicine-tab-btn');
    if (!btn) return;

    const status = btn.getAttribute('data-status');

    // Update button states for ALL tabs across all sections
    document.querySelectorAll('.medicine-tab-btn').forEach(function(b) {
        b.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
        b.classList.add('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
    });

    // Set ALL tabs with the same status as active (across all sections)
    document.querySelectorAll('.medicine-tab-btn[data-status="' + status + '"]').forEach(function(b) {
        b.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
        b.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
    });

    // Hide all medicine sections
    document.getElementById('availableSection').classList.add('hidden');
    document.getElementById('outOfStockSection').classList.add('hidden');
    document.getElementById('expiredSection').classList.add('hidden');

    // Show the selected section and load data
    if (status === 'available') {
        document.getElementById('availableSection').classList.remove('hidden');
        if (window.currentAvailableSearchTerm === '') {
            performAvailableSearch('', 1);
        }
    } else if (status === 'outOfStock') {
        document.getElementById('outOfStockSection').classList.remove('hidden');
        if (window.currentOutOfStockSearchTerm === '') {
            performOutOfStockSearch('', 1);
        }
    } else if (status === 'expired') {
        document.getElementById('expiredSection').classList.remove('hidden');
        if (window.currentExpiredSearchTerm === '') {
            performExpiredSearch('', 1);
        }
    }
});

// Global variables for search terms
window.currentAvailableSearchTerm = '';
window.currentOutOfStockSearchTerm = '';
window.currentExpiredSearchTerm = '';

// Search functions for each medicine type - following inventory.php pattern
function performAvailableSearch(searchTerm, page = 1) {
    // Show loading state
    const availableTableBody = document.getElementById('availableTableBody');
    if (availableTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentAvailableSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentAvailableSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_available_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateAvailableTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_available_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateAvailableTable(data.medicines, data.pagination);
            } else {
                console.error('Search error:', data.message);
                if (availableTableBody) {
                    availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (availableTableBody) {
                availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (availableTableBody) {
            availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

function performOutOfStockSearch(searchTerm, page = 1) {
    // Show loading state
    const outOfStockTableBody = document.getElementById('outOfStockTableBody');
    if (outOfStockTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentOutOfStockSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentOutOfStockSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_out_of_stock_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateOutOfStockTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_out_of_stock_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateOutOfStockTable(data.medicines, data.pagination);
            } else {
                console.error('Search error:', data.message);
                if (outOfStockTableBody) {
                    outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (outOfStockTableBody) {
                outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (outOfStockTableBody) {
            outOfStockTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

function performExpiredSearch(searchTerm, page = 1) {
    // Show loading state
    const expiredTableBody = document.getElementById('expiredTableBody');
    if (expiredTableBody) {
        // No loading state for seamless real-time search
    }
    
    // Store search term for pagination
    window.currentExpiredSearchTerm = searchTerm;
    
    // If search is cleared, show all data without page reload
    if (!searchTerm || searchTerm.trim() === '') {
        window.currentExpiredSearchTerm = null;
        // Make AJAX request to get all data without search filter
        fetch('search_expired_medicines.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=&page=${page}`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateExpiredTable(data.medicines, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
        return;
    }
    
    // Make AJAX request to server
    fetch('search_expired_medicines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                updateExpiredTable(data.medicines, data.pagination);
            } else {
                console.error('Search error:', data.message);
                if (expiredTableBody) {
                    expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                }
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            if (expiredTableBody) {
                expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (expiredTableBody) {
            expiredTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
        }
    });
}

// Update table functions - following inventory.php pattern
function updateAvailableTable(medicines, pagination = null) {
    const availableTableBody = document.getElementById('availableTableBody');
    if (!availableTableBody) return;
    
    if (medicines.length === 0) {
        availableTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('availableRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when results are found
    const paginationContainer = document.getElementById('availableRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'flex';
        
        if (pagination) {
            const startRecord = pagination.start_record || 1;
            const endRecord = pagination.end_record || medicines.length;
            const infoText = document.getElementById('availableRecordsInfo');
            if (infoText) {
                infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
            }
            
            // Update pagination numbers based on search results
            updateAvailablePaginationNumbers(pagination);
        } else {
            // If no pagination data, replace the entire pagination container with simple info
            paginationContainer.innerHTML = `
            <div class="text-sm text-gray-600">
                    Showing 1 to ${medicines.length} of ${medicines.length} entries
            </div>
            `;
        }
    }
    
    let html = '';
    medicines.forEach(medicine => {
        html += `
            <tr class="hover:bg-gray-50 available-row" data-name="${medicine.name.toLowerCase()}" data-category="${medicine.dosage.toLowerCase()}">
                <td class="px-6 py-4 whitespace-nowrap w-1/3">
                    <div class="text-sm font-medium text-gray-900">${medicine.name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.dosage}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.formatted_expiry}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <div class="text-sm text-gray-900">${medicine.quantity}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap w-1/6">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${medicine.status_class}">
                        ${medicine.status}
                    </span>
                </td>
            </tr>
        `;
    });
    
    availableTableBody.innerHTML = html;
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentAvailablePagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

function updateOutOfStockTable(medicines, pagination) {
    const tbody = document.getElementById('outOfStockTableBody');
    tbody.innerHTML = '';
    
    if (medicines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('outOfStockRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when there are results
    const paginationContainer = document.getElementById('outOfStockRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'block';
    }
    
    medicines.forEach(med => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 out-of-stock-row';
        row.setAttribute('data-name', med.name.toLowerCase());
        row.setAttribute('data-category', med.dosage.toLowerCase());
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap w-1/3">
                <div class="text-sm font-medium text-gray-900">${med.name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.dosage}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.formatted_expiry}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.quantity}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${med.status_class}">
                    ${med.status}
                </span>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateOutOfStockPagination(pagination);
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentOutOfStockPagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

function updateExpiredTable(medicines, pagination) {
    const tbody = document.getElementById('expiredTableBody');
    tbody.innerHTML = '';
    
    if (medicines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        // Hide pagination when no results
        const paginationContainer = document.getElementById('expiredRecordsInfo').parentElement;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        return;
    }
    
    // Show pagination when there are results
    const paginationContainer = document.getElementById('expiredRecordsInfo').parentElement;
    if (paginationContainer) {
        paginationContainer.style.display = 'block';
    }
    
    medicines.forEach(med => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 expired-row';
        row.setAttribute('data-name', med.name.toLowerCase());
        row.setAttribute('data-category', med.dosage.toLowerCase());
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap w-1/3">
                <div class="text-sm font-medium text-gray-900">${med.name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.dosage}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.formatted_expiry}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <div class="text-sm text-gray-900">${med.quantity}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap w-1/6">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${med.status_class}">
                    ${med.status}
                </span>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateExpiredPagination(pagination);
    
    // Store pagination data for tab counts
    if (pagination) {
        window.currentExpiredPagination = pagination;
    }
    
    // Update tab counts
    updateTabCounts();
}

// Pagination update functions
function updateAvailablePagination(pagination) {
    const recordsInfo = document.getElementById('availableRecordsInfo');
    const paginationNav = document.getElementById('availablePaginationNav');
    
    recordsInfo.textContent = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
    
    if (pagination.total_pages <= 1) {
        paginationNav.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Previous button
    if (pagination.current_page > 1) {
        paginationHTML += `<a href="#" class="available-pagination-link min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-l-lg" data-page="${pagination.current_page - 1}" aria-label="Previous">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
        </a>`;
    } else {
        paginationHTML += `<button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-300 cursor-not-allowed rounded-l-lg" aria-label="Previous">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
        </button>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            paginationHTML += `<button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">${i}</button>`;
        } else {
            paginationHTML += `<a href="#" class="available-pagination-link min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" data-page="${i}">${i}</a>`;
        }
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        paginationHTML += `<a href="#" class="available-pagination-link min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-r-lg" data-page="${pagination.current_page + 1}" aria-label="Next">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
        </a>`;
    } else {
        paginationHTML += `<button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-300 cursor-not-allowed rounded-r-lg" aria-label="Next">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
        </button>`;
    }
    
    paginationNav.innerHTML = paginationHTML;
}

function updateOutOfStockPagination(pagination) {
    const recordsInfo = document.getElementById('outOfStockRecordsInfo');
    const paginationNav = document.getElementById('outOfStockPaginationNav');
    
    recordsInfo.textContent = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    // Clear existing pagination
    paginationNav.innerHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        const prevBtn = document.createElement('a');
        const searchParam = window.currentOutOfStockSearchTerm ? `&search=${encodeURIComponent(window.currentOutOfStockSearchTerm)}` : '';
        prevBtn.href = `?outOfStock_page=${currentPage - 1}${searchParam}`;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    } else {
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.disabled = true;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-300 cursor-not-allowed rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    }
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300';
            pageBtn.textContent = i;
            paginationNav.appendChild(pageBtn);
        } else {
            const pageLink = document.createElement('a');
            const searchParam = window.currentOutOfStockSearchTerm ? `&search=${encodeURIComponent(window.currentOutOfStockSearchTerm)}` : '';
            pageLink.href = `?outOfStock_page=${i}${searchParam}`;
            pageLink.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
            pageLink.textContent = i;
            paginationNav.appendChild(pageLink);
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        const nextBtn = document.createElement('a');
        const searchParam = window.currentOutOfStockSearchTerm ? `&search=${encodeURIComponent(window.currentOutOfStockSearchTerm)}` : '';
        nextBtn.href = `?outOfStock_page=${currentPage + 1}${searchParam}`;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    } else {
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.disabled = true;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-300 cursor-not-allowed rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    }
}

function updateExpiredPagination(pagination) {
    const recordsInfo = document.getElementById('expiredRecordsInfo');
    const paginationNav = document.getElementById('expiredPaginationNav');
    
    recordsInfo.textContent = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    // Clear existing pagination
    paginationNav.innerHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        const prevBtn = document.createElement('a');
        const searchParam = window.currentExpiredSearchTerm ? `&search=${encodeURIComponent(window.currentExpiredSearchTerm)}` : '';
        prevBtn.href = `?expired_page=${currentPage - 1}${searchParam}`;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    } else {
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.disabled = true;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-300 cursor-not-allowed rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    }
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300';
            pageBtn.textContent = i;
            paginationNav.appendChild(pageBtn);
        } else {
            const pageLink = document.createElement('a');
            const searchParam = window.currentExpiredSearchTerm ? `&search=${encodeURIComponent(window.currentExpiredSearchTerm)}` : '';
            pageLink.href = `?expired_page=${i}${searchParam}`;
            pageLink.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
            pageLink.textContent = i;
            paginationNav.appendChild(pageLink);
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        const nextBtn = document.createElement('a');
        const searchParam = window.currentExpiredSearchTerm ? `&search=${encodeURIComponent(window.currentExpiredSearchTerm)}` : '';
        nextBtn.href = `?expired_page=${currentPage + 1}${searchParam}`;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    } else {
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.disabled = true;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-300 cursor-not-allowed rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    }
}

// Pagination functions - following inventory.php pattern
function updateAvailablePaginationNumbers(pagination) {
    const paginationNav = document.getElementById('availablePaginationNav');
    if (!paginationNav) return;
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    // Clear existing pagination
    paginationNav.innerHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        const prevBtn = document.createElement('a');
        const searchParam = window.currentAvailableSearchTerm ? `&search=${encodeURIComponent(window.currentAvailableSearchTerm)}` : '';
        prevBtn.href = `?available_page=${currentPage - 1}${searchParam}`;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    } else {
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.disabled = true;
        prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-300 cursor-not-allowed rounded-l-lg';
        prevBtn.setAttribute('aria-label', 'Previous');
        prevBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(prevBtn);
    }
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300';
            pageBtn.textContent = i;
            paginationNav.appendChild(pageBtn);
        } else {
            const pageLink = document.createElement('a');
            const searchParam = window.currentAvailableSearchTerm ? `&search=${encodeURIComponent(window.currentAvailableSearchTerm)}` : '';
            pageLink.href = `?available_page=${i}${searchParam}`;
            pageLink.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
            pageLink.textContent = i;
            paginationNav.appendChild(pageLink);
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        const nextBtn = document.createElement('a');
        const searchParam = window.currentAvailableSearchTerm ? `&search=${encodeURIComponent(window.currentAvailableSearchTerm)}` : '';
        nextBtn.href = `?available_page=${currentPage + 1}${searchParam}`;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    } else {
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.disabled = true;
        nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm  border border-gray-200 text-gray-300 cursor-not-allowed rounded-r-lg';
        nextBtn.setAttribute('aria-label', 'Next');
        nextBtn.innerHTML = `
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        `;
        paginationNav.appendChild(nextBtn);
    }
}

// Search input event listeners with debouncing - following inventory.php pattern
let availableSearchTimeout;
let outOfStockSearchTimeout;
let expiredSearchTimeout;

document.getElementById('availableSearch').addEventListener('input', function() {
    const searchTerm = this.value;
    
    // Clear existing timeout
    clearTimeout(availableSearchTimeout);
    
    // Set new timeout for debounced search
    availableSearchTimeout = setTimeout(() => {
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            performAvailableSearch(searchTerm, 1); // Always start from page 1 for new searches
        }
    }, 300);
});

document.getElementById('outOfStockSearch').addEventListener('input', function() {
    const searchTerm = this.value;
    
    // Clear existing timeout
    clearTimeout(outOfStockSearchTimeout);
    
    // Set new timeout for debounced search
    outOfStockSearchTimeout = setTimeout(() => {
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            performOutOfStockSearch(searchTerm, 1); // Always start from page 1 for new searches
        }
    }, 300);
});

document.getElementById('expiredSearch').addEventListener('input', function() {
    const searchTerm = this.value;
    
    // Clear existing timeout
    clearTimeout(expiredSearchTimeout);
    
    // Set new timeout for debounced search
    expiredSearchTimeout = setTimeout(() => {
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            performExpiredSearch(searchTerm, 1); // Always start from page 1 for new searches
        }
    }, 300);
});

// Pagination click handlers - following inventory.php pattern
document.addEventListener('click', function(e) {
    // Check if it's a pagination link for available medicines
    if (e.target.closest('#availablePaginationNav a')) {
        const link = e.target.closest('a');
        const href = link.getAttribute('href');
        
        // Always prevent default and use AJAX for pagination
        if (href.includes('available_page=')) {
            e.preventDefault();
            
            // Extract page number from href
            const pageMatch = href.match(/available_page=(\d+)/);
            if (pageMatch) {
                const page = parseInt(pageMatch[1]);
                // Use search function with current search term (or empty if no search)
                const searchTerm = window.currentAvailableSearchTerm || '';
                performAvailableSearch(searchTerm, page);
            }
        }
    }
    
    // Check if it's a pagination link for out of stock medicines
    if (e.target.closest('#outOfStockPaginationNav a')) {
        const link = e.target.closest('a');
        const href = link.getAttribute('href');
        
        // Always prevent default and use AJAX for pagination
        if (href.includes('outOfStock_page=')) {
            e.preventDefault();
            
            // Extract page number from href
            const pageMatch = href.match(/outOfStock_page=(\d+)/);
            if (pageMatch) {
                const page = parseInt(pageMatch[1]);
                // Use search function with current search term (or empty if no search)
                const searchTerm = window.currentOutOfStockSearchTerm || '';
                performOutOfStockSearch(searchTerm, page);
            }
        }
    }
    
    // Check if it's a pagination link for expired medicines
    if (e.target.closest('#expiredPaginationNav a')) {
        const link = e.target.closest('a');
        const href = link.getAttribute('href');
        
        // Always prevent default and use AJAX for pagination
        if (href.includes('expired_page=')) {
            e.preventDefault();
            
            // Extract page number from href
            const pageMatch = href.match(/expired_page=(\d+)/);
            if (pageMatch) {
                const page = parseInt(pageMatch[1]);
                // Use search function with current search term (or empty if no search)
                const searchTerm = window.currentExpiredSearchTerm || '';
                performExpiredSearch(searchTerm, page);
            }
        }
    }
});

// Function to update tab counts
function updateTabCounts() {
    // Update available count
    const availableCounts = document.querySelectorAll('#availableCount, #availableCount2, #availableCount3');
    availableCounts.forEach(count => {
        if (window.currentAvailablePagination) {
            count.textContent = window.currentAvailablePagination.total_records || 0;
        }
    });
    
    // Update out of stock count
    const outOfStockCounts = document.querySelectorAll('#outOfStockCount, #outOfStockCount2, #outOfStockCount3');
    outOfStockCounts.forEach(count => {
        if (window.currentOutOfStockPagination) {
            count.textContent = window.currentOutOfStockPagination.total_records || 0;
        }
    });
    
    // Update expired count
    const expiredCounts = document.querySelectorAll('#expiredCount, #expiredCount2, #expiredCount3');
    expiredCounts.forEach(count => {
        if (window.currentExpiredPagination) {
            count.textContent = window.currentExpiredPagination.total_records || 0;
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
