<?php
// Usage: set $inputId before including this file.
// Renders a Bootstrap modal with Cropper.js controls for the given file input id.
$inputId = $inputId ?? 'profile_image';
?>
<div class="modal fade" id="imageEditModal-<?php echo $inputId; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-crop"></i> Edit Profile Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div style="max-height: 400px; overflow: hidden;">
                    <img class="crop-image" src="" alt="Edit preview" style="max-width: 100%;">
                </div>
                <div class="mt-3 d-flex justify-content-center gap-2">
                    <!-- Rotate controls removed per request -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-crop">Cancel</button>
                <button type="button" class="btn btn-primary apply-crop"><i class="bi bi-check-circle"></i> Apply</button>
            </div>
        </div>
    </div>
</div>
