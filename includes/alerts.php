<?php
/**
 * Alerts / Flash Messages Component - SSMPAS
 * Include this partial where you want alerts to appear (typically after topbar).
 */
require_once __DIR__ . '/helpers.php';

$flash = get_flash();
if ($flash):
    $type  = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info');
    $msg   = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
?>
<div class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : ($type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> me-2"></i>
    <span><?= $msg ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
