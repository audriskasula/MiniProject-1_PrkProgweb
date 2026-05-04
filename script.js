document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Auto-hide Alert Messages after 5 seconds
    const alerts = document.querySelectorAll('div[style*="background-color: #dcfce7"], div[style*="background-color: #fee2e2"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 2. Image Upload Preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                // Check if preview img already exists
                let previewContainer = input.nextElementSibling;
                if (!previewContainer || !previewContainer.classList.contains('img-preview')) {
                    previewContainer = document.createElement('img');
                    previewContainer.classList.add('img-preview');
                    previewContainer.style.maxWidth = '100%';
                    previewContainer.style.maxHeight = '200px';
                    previewContainer.style.marginTop = '10px';
                    previewContainer.style.borderRadius = '8px';
                    previewContainer.style.objectFit = 'cover';
                    input.parentNode.insertBefore(previewContainer, input.nextSibling);
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    });

    // 3. Progress Bar Animation on Load
    const progressFills = document.querySelectorAll('.progress-fill');
    progressFills.forEach(fill => {
        const targetWidth = fill.style.width;
        fill.style.width = '0%';
        setTimeout(() => {
            fill.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
            fill.style.width = targetWidth;
        }, 100);
    });

    // 4. Client-side validation for donasi nominal
    const donasiForm = document.querySelector('form[action*="donasi.php"]');
    if (donasiForm) {
        donasiForm.addEventListener('submit', function(e) {
            const nominalInput = donasiForm.querySelector('input[name="nominal"]');
            if (nominalInput && parseInt(nominalInput.value) < 10000) {
                e.preventDefault();
                alert('Mohon maaf, minimal donasi adalah Rp 10.000');
                nominalInput.focus();
            }
        });
    }

});
