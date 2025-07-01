document.addEventListener('DOMContentLoaded', function () {
    const imageInput = document.querySelector('input[name="image"]');
    const imagePreview = document.getElementById('image-preview');
    const image = document.getElementById('image');
    const cropButton = document.getElementById('crop-button');
    let cropper;

    // Show preview and initialize Cropper.js when an image is selected
    imageInput.addEventListener('change', function (event) {
        const files = event.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                image.src = e.target.result;
                imagePreview.style.display = 'block';
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(image, {
                    aspectRatio: 16 / 9, // Adjust as needed (e.g., 1 for square)
                    viewMode: 1,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    // Crop the image and submit the form
    cropButton.addEventListener('click', function () {
        if (cropper) {
            const croppedCanvas = cropper.getCroppedCanvas();
            croppedCanvas.toBlob(function (blob) {
                const formData = new FormData();
                formData.append('image', blob, 'cropped-image.jpg');

                // Append other form fields
                const form = document.querySelector('form');
                const formElements = form.elements;
                for (let i = 0; i < formElements.length; i++) {
                    const element = formElements[i];
                    if (element.name && element.name !== 'image') {
                        formData.append(element.name, element.value);
                    }
                }

                // Submit the form via AJAX
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                }).then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error uploading image');
                    }
                }).catch(error => {
                    console.error('Upload failed:', error);
                    alert('Error uploading image');
                });
            }, 'image/jpeg', 0.8); // 80% quality to reduce file size
        }
    });
});