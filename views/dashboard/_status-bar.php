<?php

/**
 * แถบสัดส่วนแนวนอน (segmented bar) + legend แบบกริด 2 คอลัมน์ — ใช้ซ้ำ 3 จุดในหน้า dashboard/index
 * (สถานะการดำเนินโครงการ, ผลการตรวจสอบ, สถานะโครงการ) แต่ละ segment/legend chip คลิกได้เพื่อกรอง
 * ตารางด้านล่าง (toggle: คลิกซ้ำอันที่กรองอยู่แล้วเพื่อล้างตัวกรอง) — ทำเป็นลิงก์ธรรมดา ไม่ต้องพึ่ง JS
 *
 * @var yii\web\View $this
 * @var array $labels ['key' => 'ป้ายชื่อ', ...]
 * @var array $counts ['key' => int, ...]
 * @var array $colors ['key' => '#rrggbb', ...]
 * @var string $filterParam ชื่อ query param ที่ใช้กรอง เช่น 'status'
 * @var string|null $currentFilter ค่าที่กำลังกรองอยู่ตอนนี้ (ของ filterParam นี้)
 * @var array $baseUrlParams params พื้นฐานของลิงก์ (route, start_date, end_date, ตัวกรองอีกฝั่งที่ต้องรักษาไว้) — ไม่รวม filterParam นี้และไม่รวม page param ใดๆ (จะได้รีเซ็ตหน้ากลับไปหน้า 1 เองเมื่อกรองเปลี่ยน)
 */

use app\helpers\ColorHelper;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$total = array_sum($counts);
?>
<?php if ($total === 0): ?>
    <p class="text-body-secondary small mb-0">ไม่มีข้อมูลในช่วงที่เลือก</p>
<?php else: ?>
    <div class="status-bar">
        <?php foreach ($labels as $key => $label): ?>
            <?php $count = $counts[$key] ?? 0; ?>
            <?php if ($count <= 0) continue; ?>
            <?php $params = $baseUrlParams; ?>
            <?php if ($currentFilter !== $key) {
                $params[$filterParam] = $key;
            } ?>
            <?= Html::a('', Url::to($params), [
                'class' => 'status-bar-segment' . ($currentFilter === $key ? ' is-active' : ''),
                'style' => 'width: ' . round($count / $total * 100, 2) . '%; background-color: ' . ColorHelper::lighten($colors[$key], .4) . ';',
                'title' => $label . ' (' . $count . ')',
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="status-legend">
        <?php foreach ($labels as $key => $label): ?>
            <?php $count = $counts[$key] ?? 0; ?>
            <?php $params = $baseUrlParams; ?>
            <?php if ($currentFilter !== $key) {
                $params[$filterParam] = $key;
            } ?>
            <?= Html::a(
                Html::tag('span', '', ['class' => 'status-legend-swatch', 'style' => 'background-color: ' . $colors[$key] . ';'])
                . Html::tag('span', Html::encode($label) . ' (' . $count . ')'),
                Url::to($params),
                ['class' => 'status-legend-item' . ($currentFilter === $key ? ' is-active' : '')]
            ) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
