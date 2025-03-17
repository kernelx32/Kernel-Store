/**
 * KernelStore Admin Panel JavaScript
 * Handles UI interactions, form validations, and dynamic content
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    
    if (sidebarToggle && mobileSidebar && closeSidebar) {
        const sidebarContent = mobileSidebar.querySelector('.transform');
        
        sidebarToggle.addEventListener('click', () => {
            mobileSidebar.classList.remove('hidden');
            setTimeout(() => {
                sidebarContent.classList.remove('translate-x-full');
            }, 10);
        });
        
        closeSidebar.addEventListener('click', closeMobileSidebar);
        mobileSidebar.addEventListener('click', (e) => {
            if (e.target === mobileSidebar) {
                closeMobileSidebar();
            }
        });
        
        function closeMobileSidebar() {
            sidebarContent.classList.add('translate-x-full');
            setTimeout(() => {
                mobileSidebar.classList.add('hidden');
            }, 300);
        }
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        if (!form.classList.contains('no-validation')) {
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Get all required inputs
                const requiredInputs = form.querySelectorAll('[required]');
                
                requiredInputs.forEach(input => {
                    // Remove existing error messages
                    const existingError = input.parentElement.querySelector('.error-message');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Check if input is empty
                    if (!input.value.trim()) {
                        isValid = false;
                        
                        // Create error message
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                        errorMessage.textContent = 'This field is required';
                        
                        // Add error message after input
                        input.parentElement.appendChild(errorMessage);
                        
                        // Add error class to input
                        input.classList.add('border-red-500');
                    } else {
                        // Remove error class
                        input.classList.remove('border-red-500');
                        
                        // Validate email format
                        if (input.type === 'email' && !validateEmail(input.value)) {
                            isValid = false;
                            
                            // Create error message
                            const errorMessage = document.createElement('div');
                            errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                            errorMessage.textContent = 'Please enter a valid email address';
                            
                            // Add error message after input
                            input.parentElement.appendChild(errorMessage);
                            
                            // Add error class to input
                            input.classList.add('border-red-500');
                        }
                        
                        // Validate number min/max
                        if (input.type === 'number') {
                            const min = parseFloat(input.getAttribute('min'));
                            const max = parseFloat(input.getAttribute('max'));
                            const value = parseFloat(input.value);
                            
                            if (!isNaN(min) && value < min) {
                                isValid = false;
                                
                                // Create error message
                                const errorMessage = document.createElement('div');
                                errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                                errorMessage.textContent = `Value must be at least ${min}`;
                                
                                // Add error message after input
                                input.parentElement.appendChild(errorMessage);
                                
                                // Add error class to input
                                input.classList.add('border-red-500');
                            }
                            
                            if (!isNaN(max) && value > max) {
                                isValid = false;
                                
                                // Create error message
                                const errorMessage = document.createElement('div');
                                errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                                errorMessage.textContent = `Value must be at most ${max}`;
                                
                                // Add error message after input
                                input.parentElement.appendChild(errorMessage);
                                
                                // Add error class to input
                                input.classList.add('border-red-500');
                            }
                        }
                    }
                });
                
                // Check password confirmation
                const password = form.querySelector('input[name="password"]');
                const confirmPassword = form.querySelector('input[name="confirm_password"]');
                
                if (password && confirmPassword && password.value && confirmPassword.value && password.value !== confirmPassword.value) {
                    isValid = false;
                    
                    // Remove existing error message
                    const existingError = confirmPassword.parentElement.querySelector('.error-message');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Create error message
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                    errorMessage.textContent = 'Passwords do not match';
                    
                    // Add error message after input
                    confirmPassword.parentElement.appendChild(errorMessage);
                    
                    // Add error class to input
                    confirmPassword.classList.add('border-red-500');
                }
                
                // Prevent form submission if not valid
                if (!isValid) {
                    event.preventDefault();
                    
                    // Scroll to first error
                    const firstError = form.querySelector('.error-message');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
    });
    
    // Email validation function
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
    
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (slugInput.value === '' || slugInput.dataset.autoGenerate === 'true') {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugInput.dataset.autoGenerate = 'true';
            }
        });
        
        slugInput.addEventListener('input', function() {
            this.dataset.autoGenerate = 'false';
        });
    }
    
    // Image preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.parentElement.querySelector('.image-preview');
            if (!preview) return;
            
            while (preview.firstChild) {
                preview.removeChild(preview.firstChild);
            }
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover';
                    preview.appendChild(img);
                    preview.classList.remove('border-dashed');
                };
                
                reader.readAsDataURL(this.files[0]);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-preview-placeholder';
                placeholder.innerHTML = '<i class="fas fa-image text-gray-400 text-3xl"></i><p class="mt-2 text-gray-400">No image selected</p>';
                preview.appendChild(placeholder);
                preview.classList.add('border-dashed');
            }
        });
        
        // Trigger change event to initialize preview
        const event = new Event('change');
        input.dispatchEvent(event);
    });
    
    // Confirm delete
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
    
    // Tabs
    const tabButtons = document.querySelectorAll('[data-tab]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            const tabContent = document.getElementById(tabId);
            
            if (!tabContent) return;
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show selected tab content
            tabContent.classList.remove('hidden');
            
            // Update active state of tab buttons
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.classList.remove('bg-indigo-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            
            this.classList.remove('bg-gray-200', 'text-gray-700');
            this.classList.add('bg-indigo-600', 'text-white');
        });
    });
    
    // Dismissible alerts
    const dismissButtons = document.querySelectorAll('.alert-dismiss');
    
    dismissButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                alert.classList.add('opacity-0');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        });
    });
    
    // Toggle switches
    const toggleSwitches = document.querySelectorAll('.toggle-switch');
    
    toggleSwitches.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.querySelector('input[type="checkbox"]');
            const track = this.querySelector('.toggle-track');
            const thumb = this.querySelector('.toggle-thumb');
            
            if (input.checked) {
                track.classList.remove('bg-indigo-600');
                track.classList.add('bg-gray-300');
                thumb.classList.remove('translate-x-5');
                thumb.classList.add('translate-x-0');
            } else {
                track.classList.remove('bg-gray-300');
                track.classList.add('bg-indigo-600');
                thumb.classList.remove('translate-x-0');
                thumb.classList.add('translate-x-5');
            }
            
            input.checked = !input.checked;
            
            // Trigger change event
            const event = new Event('change');
            input.dispatchEvent(event);
        });
    });
    
    // Sortable tables
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = this.cellIndex;
            const sortKey = this.getAttribute('data-sort');
            const sortDirection = this.classList.contains('sort-asc') ? 'desc' : 'asc';
            
            // Update sort direction indicators
            sortableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
                h.querySelector('.sort-icon')?.remove();
            });
            
            this.classList.add(`sort-${sortDirection}`);
            
            // Add sort icon
            const sortIcon = document.createElement('span');
            sortIcon.className = 'sort-icon ml-1';
            sortIcon.innerHTML = sortDirection === 'asc' ? '↑' : '↓';
            this.appendChild(sortIcon);
            
            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.cells[column].textContent.trim();
                const bValue = b.cells[column].textContent.trim();
                
                if (sortKey === 'number') {
                    return sortDirection === 'asc' 
                        ? parseFloat(aValue) - parseFloat(bValue)
                        : parseFloat(bValue) - parseFloat(aValue);
                } else if (sortKey === 'date') {
                    return sortDirection === 'asc'
                        ? new Date(aValue) - new Date(bValue)
                        : new Date(bValue) - new Date(aValue);
                } else {
                    return sortDirection === 'asc'
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                }
            });
            
            // Reorder rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // Rich text editor (simple version)
    const richTextAreas = document.querySelectorAll('.rich-text-editor');
    
    richTextAreas.forEach(textarea => {
        // Create toolbar
        const toolbar = document.createElement('div');
        toolbar.className = 'rich-text-toolbar flex space-x-2 p-2 bg-gray-100 border border-gray-300 rounded-t-md';
        
        // Bold button
        const boldButton = document.createElement('button');
        boldButton.type = 'button';
        boldButton.className = 'p-1 hover:bg-gray-200 rounded';
        boldButton.innerHTML = '<i class="fas fa-bold"></i>';
        boldButton.addEventListener('click', () => {
            document.execCommand('bold', false, null);
            textarea.focus();
        });
        
        // Italic button
        const italicButton = document.createElement('button');
        italicButton.type = 'button';
        italicButton.className = 'p-1 hover:bg-gray-200 rounded';
        italicButton.innerHTML = '<i class="fas fa-italic"></i>';
        italicButton.addEventListener('click', () => {
            document.execCommand('italic', false, null);
            textarea.focus();
        });
        
        // Underline button
        const underlineButton = document.createElement('button');
        underlineButton.type = 'button';
        underlineButton.className = 'p-1 hover:bg-gray-200 rounded';
        underlineButton.innerHTML = '<i class="fas fa-underline"></i>';
        underlineButton.addEventListener('click', () => {
            document.execCommand('underline', false, null);
            textarea.focus();
        });
        
        // List button
        const listButton = document.createElement('button');
        listButton.type = 'button';
        listButton.className = 'p-1 hover:bg-gray-200 rounded';
        listButton.innerHTML = '<i class="fas fa-list-ul"></i>';
        listButton.addEventListener('click', () => {
            document.execCommand('insertUnorderedList', false, null);
            textarea.focus();
        });
        
        // Add buttons to toolbar
        toolbar.appendChild(boldButton);
        toolbar.appendChild(italicButton);
        toolbar.appendChild(underlineButton);
        toolbar.appendChild(listButton);
        
        // Create editable div
        const editable = document.createElement('div');
        editable.className = 'rich-text-content p-3 border border-gray-300 border-t-0 rounded-b-md min-h-[150px] focus:outline-none';
        editable.contentEditable = true;
        editable.innerHTML = textarea.value;
        
        // Update textarea value when editable div changes
        editable.addEventListener('input', () => {
            textarea.value = editable.innerHTML;
        });
        
        // Hide textarea and insert toolbar and editable div
        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(toolbar, textarea);
        textarea.parentNode.insertBefore(editable, textarea.nextSibling);
    });
    
    // Date picker (simple version without dependencies)
    const datePickers = document.querySelectorAll('.date-picker');
    
    datePickers.forEach(input => {
        // Add calendar icon
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        
        const icon = document.createElement('div');
        icon.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none';
        icon.innerHTML = '<i class="fas fa-calendar"></i>';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(icon);
        
        // Format date on blur
        input.addEventListener('blur', function() {
            if (this.value) {
                const date = new Date(this.value);
                if (!isNaN(date.getTime())) {
                    this.value = date.toISOString().split('T')[0];
                }
            }
        });
    });
    
    // Initialize any charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Sales chart
        const salesChartCanvas = document.getElementById('salesChart');
        if (salesChartCanvas) {
            new Chart(salesChartCanvas, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Sales',
                        data: salesChartCanvas.dataset.values ? JSON.parse(salesChartCanvas.dataset.values) : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Users chart
        const usersChartCanvas = document.getElementById('usersChart');
        if (usersChartCanvas) {
            new Chart(usersChartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'New Users',
                        data: usersChartCanvas.dataset.values ? JSON.parse(usersChartCanvas.dataset.values) : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
});