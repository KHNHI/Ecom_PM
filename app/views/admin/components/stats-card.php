<?php
/**
 * Stats Card Component - Pure MVC Component
 * Hiển thị thống kê với data từ database
 */

// Biến được truyền:
// $value - giá trị thống kê
// $label - nhãn thống kê
// $percentage - phần trăm thay đổi
// $comparison - so sánh với kỳ trước
// $trend - xu hướng (positive/negative)
?>

<!-- Statistics Card Component -->
<div class="stats-card">
    <div class="stats-value" data-value="<?= htmlspecialchars($value ?? '0') ?>">
        <?= htmlspecialchars($value ?? '0') ?>
    </div>
    <div class="stats-label" data-label="<?= htmlspecialchars($label ?? 'Label') ?>">
        <?= htmlspecialchars($label ?? 'Label') ?>
    </div>
    <div class="stats-change <?= $trend ?? 'positive' ?>">
        <img src="https://cdn-icons-png.flaticon.com/512/5610/5610944.png" alt="Up" width="12" height="12">
        <span data-percentage="<?= htmlspecialchars($percentage ?? '0%') ?>">
            <?= htmlspecialchars($percentage ?? '0%') ?>
        </span> 
        <small class="text-muted" data-comparison="<?= htmlspecialchars($comparison ?? '') ?>">
            <?= htmlspecialchars($comparison ?? '') ?>
        </small>
    </div>
</div>
