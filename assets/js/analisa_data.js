/**
 * JAVASCRIPT UNTUK HALAMAN ANALISA DATA APRIORI
 * 
 * File ini berisi fungsi-fungsi JavaScript untuk meningkatkan
 * user experience pada halaman analisa data Apriori
 */

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Auto-submit form when period changes
    $('#start_period, #end_period').change(function() {
        validatePeriodRange();
    });
    
    // Form validation and AJAX submission
    $('#analysisForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        if (!validateForm()) {
            return false;
        }
        
        submitFormAjax();
    });
    
    // Initialize parameter tooltips
    initializeParameterTooltips();
    
    // Initialize result animations
    initializeResultAnimations();
});

/**
 * Validasi rentang periode
 */
function validatePeriodRange() {
    const startPeriod = $('#start_period').val();
    const endPeriod = $('#end_period').val();
    
    if (startPeriod && endPeriod) {
        if (startPeriod > endPeriod) {
            showAlert('Periode mulai tidak boleh lebih besar dari periode akhir!', 'warning');
            $('#end_period').val('');
            return false;
        }
    }
    return true;
}

/**
 * Validasi form sebelum submit
 */
function validateForm() {
    const minSupport = parseFloat($('#min_support').val());
    const minConfidence = parseFloat($('#min_confidence').val());
    
    // Validasi minimum support
    if (minSupport < 1 || minSupport > 100) {
        showAlert('Minimum Support harus antara 1% - 100%', 'error');
        $('#min_support').focus();
        return false;
    }
    
    // Validasi minimum confidence
    if (minConfidence < 1 || minConfidence > 100) {
        showAlert('Minimum Confidence harus antara 1% - 100%', 'error');
        $('#min_confidence').focus();
        return false;
    }
    
    // Validasi periode
    if (!validatePeriodRange()) {
        return false;
    }
    
    return true;
}

/**
 * Tampilkan loading indicator
 */
function showLoadingIndicator() {
    const loadingHtml = `
        <div id="loadingIndicator" class="text-center my-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Sedang memproses analisis Apriori...</p>
            <small class="text-muted">Proses ini mungkin membutuhkan waktu beberapa detik</small>
        </div>
    `;
    
    // Tambahkan loading indicator setelah form
    $('#analysisForm').after(loadingHtml);
    
    // Disable submit button
    $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
}

/**
 * Sembunyikan loading indicator
 */
function hideLoadingIndicator() {
    $('#loadingIndicator').remove();
    $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-chart-line"></i> Analisis Data');
}

/**
 * Submit form menggunakan AJAX
 */
function submitFormAjax() {
    showLoadingIndicator();
    
    const formData = $('#analysisForm').serialize() + '&analyze=1';
    
    // Debug: log form data
    console.log('Form data being sent:', formData);
    
    // Clear previous results
    $('#analysisResults').remove();
    
    $.ajax({
         url: 'analisa_data.php',
         type: 'POST',
         data: formData,
         headers: {
             'X-Requested-With': 'XMLHttpRequest'
         },
         success: function(response) {
            hideLoadingIndicator();
            
            // Debug: log the response
            console.log('AJAX Response:', response);
            
            // Check if response is HTML or contains results
            if (response.trim().length === 0) {
                displayError('Server mengembalikan response kosong.');
                return;
            }
            
            // Extract only the results section from the response
            const $response = $(response);
            const $results = $response.find('.analysis-results');
            
            console.log('Found results elements:', $results.length);
            
            if ($results.length > 0) {
                displayResults($results.html());
            } else {
                // Check if the response itself is the results (direct HTML)
                if (response.includes('analysis-results')) {
                    displayResults(response);
                } else {
                    // If no results section found, look for error messages
                    const $errorDiv = $response.find('.alert-danger');
                    if ($errorDiv.length > 0) {
                        displayError($errorDiv.html());
                    } else {
                        // Show the raw response for debugging
                        console.log('Raw response for debugging:', response);
                        displayError('Tidak ada hasil analisis yang ditemukan. Response: ' + response.substring(0, 200));
                    }
                }
            }
        },
        error: function(xhr, status, error) {
            hideLoadingIndicator();
            console.error('Error:', error);
            displayError('Terjadi kesalahan saat melakukan analisis: ' + error);
        }
    });
}

/**
 * Tampilkan hasil analisis
 */
function displayResults(resultsHTML) {
    let $resultContainer = $('#analysisResults');
    if ($resultContainer.length === 0) {
        // Create results container if it doesn't exist
        $resultContainer = $('<div id="analysisResults" class="mt-4"></div>');
        
        // Insert after the form
        $('#analysisForm').after($resultContainer);
    }
    
    $resultContainer.html(resultsHTML);
    
    // Scroll to results
    $('html, body').animate({
        scrollTop: $resultContainer.offset().top
    }, 500);
    
    // Initialize result animations
    initializeResultAnimations();
}

/**
 * Tampilkan pesan error
 */
function displayError(errorMessage) {
    let $resultContainer = $('#analysisResults');
    if ($resultContainer.length === 0) {
        $resultContainer = $('<div id="analysisResults" class="mt-4"></div>');
        $('#analysisForm').after($resultContainer);
    }
    
    $resultContainer.html(`
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle"></i> ${errorMessage}
        </div>
    `);
    
    $('html, body').animate({
        scrollTop: $resultContainer.offset().top
    }, 500);
}

/**
 * Tampilkan alert message
 */
function showAlert(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const alertHtml = `
        <div class="alert ${alertClass[type]} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of main content
    $('main .pt-3').after(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

/**
 * Initialize parameter tooltips
 */
function initializeParameterTooltips() {
    // Support tooltip
    $('#min_support').attr({
        'data-toggle': 'tooltip',
        'data-placement': 'top',
        'title': 'Minimum persentase kemunculan kombinasi produk dalam transaksi. Nilai rendah = lebih banyak hasil, nilai tinggi = hasil lebih selektif.'
    });
    
    // Confidence tooltip
    $('#min_confidence').attr({
        'data-toggle': 'tooltip',
        'data-placement': 'top',
        'title': 'Minimum tingkat kepercayaan aturan asosiasi. Semakin tinggi nilai, semakin kuat hubungan antar produk.'
    });
    
    // Period tooltips
    $('#start_period, #end_period').attr({
        'data-toggle': 'tooltip',
        'data-placement': 'top',
        'title': 'Pilih periode untuk analisis data transaksi dalam rentang waktu tertentu.'
    });
    
    // Refresh tooltips
    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Initialize result animations
 */
function initializeResultAnimations() {
    // Animate metric cards
    $('.metric-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 100).animate({
            'opacity': '1'
        }, 500).css('transform', 'translateY(0)');
    });
    
    // Animate rule items
    $('.rule-item').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateX(-20px)'
        }).delay(index * 50).animate({
            'opacity': '1'
        }, 300).css('transform', 'translateX(0)');
    });
    
    // Add hover effects to rule items
    $('.rule-item').hover(
        function() {
            $(this).css({
                'transform': 'scale(1.02)',
                'box-shadow': '0 4px 15px rgba(0,0,0,0.15)'
            });
        },
        function() {
            $(this).css({
                'transform': 'scale(1)',
                'box-shadow': '0 2px 10px rgba(0,0,0,0.1)'
            });
        }
    );
}

/**
 * Format number dengan pemisah ribuan
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Copy rule text to clipboard
 */
function copyRuleToClipboard(ruleText) {
    navigator.clipboard.writeText(ruleText).then(function() {
        showAlert('Aturan berhasil disalin ke clipboard!', 'success');
    }).catch(function() {
        showAlert('Gagal menyalin aturan ke clipboard', 'error');
    });
}

/**
 * Export results to CSV
 */
function exportToCSV() {
    const rules = [];
    
    // Collect all rules
    $('.rule-item').each(function() {
        const antecedent = $(this).find('.badge-primary').next().text().trim();
        const consequent = $(this).find('.badge-success').next().text().trim();
        const support = $(this).find('strong').eq(0).text();
        const confidence = $(this).find('strong').eq(1).text();
        const lift = $(this).find('strong').eq(2).text();
        
        rules.push({
            antecedent: antecedent,
            consequent: consequent,
            support: support,
            confidence: confidence,
            lift: lift
        });
    });
    
    if (rules.length === 0) {
        showAlert('Tidak ada data untuk diekspor', 'warning');
        return;
    }
    
    // Create CSV content
    let csvContent = "Antecedent,Consequent,Support,Confidence,Lift\n";
    rules.forEach(rule => {
        csvContent += `"${rule.antecedent}","${rule.consequent}","${rule.support}","${rule.confidence}","${rule.lift}"\n`;
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `apriori_analysis_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Data berhasil diekspor ke CSV!', 'success');
}

/**
 * Reset form to default values
 */
function resetForm() {
    $('#min_support').val(30);
    $('#min_confidence').val(60);
    $('#start_period').val('');
    $('#end_period').val('');
    
    showAlert('Form berhasil direset ke nilai default', 'info');
}

/**
 * Show/hide advanced options
 */
function toggleAdvancedOptions() {
    $('#advancedOptions').slideToggle();
    const button = $('#toggleAdvanced');
    const isVisible = $('#advancedOptions').is(':visible');
    
    button.html(isVisible ? 
        '<i class="fas fa-chevron-up"></i> Sembunyikan Opsi Lanjutan' : 
        '<i class="fas fa-chevron-down"></i> Tampilkan Opsi Lanjutan'
    );
}

/**
 * Highlight frequent itemsets in rules
 */
function highlightFrequentItems() {
    const frequentItems = new Set();
    
    // Collect all frequent items
    $('.itemset-badge').each(function() {
        frequentItems.add($(this).text().trim());
    });
    
    // Highlight items in rules
    $('.rule-item .itemset-badge').each(function() {
        const item = $(this).text().trim();
        if (frequentItems.has(item)) {
            $(this).addClass('bg-warning text-dark');
        }
    });
}

/**
 * Filter rules by confidence level
 */
function filterRulesByConfidence(minConfidence) {
    $('.rule-item').each(function() {
        const confidence = parseFloat($(this).find('strong').eq(1).text().replace('%', ''));
        
        if (confidence >= minConfidence) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    const visibleRules = $('.rule-item:visible').length;
    showAlert(`Menampilkan ${visibleRules} aturan dengan confidence >= ${minConfidence}%`, 'info');
}

/**
 * Search rules by item name
 */
function searchRules(searchTerm) {
    if (!searchTerm) {
        $('.rule-item').show();
        return;
    }
    
    $('.rule-item').each(function() {
        const ruleText = $(this).text().toLowerCase();
        
        if (ruleText.includes(searchTerm.toLowerCase())) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    const visibleRules = $('.rule-item:visible').length;
    showAlert(`Ditemukan ${visibleRules} aturan yang mengandung "${searchTerm}"`, 'info');
}