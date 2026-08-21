// Form validation and file size check
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.querySelector('.upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('pdf_file');
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Check file size
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size must be less than 10MB');
                    return false;
                }
                
                // Check file type
                if (!file.type.includes('pdf')) {
                    e.preventDefault();
                    alert('Please upload a PDF file only');
                    return false;
                }
            }
        });
    }
    
    // Add animation to notification cards on hover
    const cards = document.querySelectorAll('.notification-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});