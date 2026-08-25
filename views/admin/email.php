<?php

/** @var yii\web\View $this */
/** @var array $projects */
/** @var array $projectCodes */
/** @var string $statusFilter */
/** @var string $startDate */
/** @var string $endDate */
/** @var app\models\ReportNotification[] $notifications */
/** @var yii\data\ActiveDataProvider $notificationsProvider */
/** @var string $notifStartDate */
/** @var string $notifEndDate */
/** @var string $notifSearch */

use app\helpers\ThaiDate;
use app\models\ReportNotification;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\helpers\Json;

$this->title = 'จัดการการแจ้งเตือน / ข่าวสาร';
$this->params['breadcrumbs'][] = $this->title;

// เนื้อหาของเทมเพลต "แจ้งเตือนส่งรายงาน" ไม่ใช่ข้อความคงที่แบบ "ข่าวสารทั่วไป" อีกต่อไป — ระบบสร้าง
// เนื้อหาหลัก (รหัสโครงการ ชื่อหัวหน้าโครงการ ชื่อโครงการ ฯลฯ) ให้อัตโนมัติแยกตามแต่ละโครงการที่เลือก
// (ดู mail/reminder.php) หัวข้ออีเมลยังแก้ไขได้ตามปกติ ส่วนกล่อง "เนื้อหา" ในโหมดนี้ใช้เป็นข้อความ
// เสริมท้ายอีเมล (ไม่บังคับ) เท่านั้น
$templateSubjects = [
    'blank' => '',
    'reminder' => 'แจ้งเตือน: กรุณารายงานความก้าวหน้าโครงการวิจัย',
];
$defaultDeadline = date('Y-m-d', strtotime('+30 days'));

$this->registerJsFile('@web/js/vendor/sweetalert2.all.min.js');
$this->registerJsFile('@web/js/confirm-submit.js');

$this->registerJs('
    var templateSubjects = ' . Json::htmlEncode($templateSubjects) . ';
    var templateSelect = document.getElementById("email-template");
    var subjectInput = document.getElementById("email-subject");
    var bodyInput = document.getElementById("email-body");
    var bodyLabel = document.getElementById("email-body-label");
    var bodyHint = document.getElementById("email-body-hint");
    var deadlineWrap = document.getElementById("deadline-field-wrap");
    var deadlineInput = document.getElementById("email-deadline");

    function applyTemplateMode() {
        var isReminder = templateSelect.value === "reminder";
        subjectInput.value = templateSubjects[templateSelect.value] || "";
        deadlineWrap.style.display = isReminder ? "" : "none";
        deadlineInput.required = isReminder;
        bodyInput.required = !isReminder;
        bodyLabel.textContent = isReminder ? "ข้อความเสริมท้ายอีเมล (ถ้ามี)" : "เนื้อหา";
        bodyHint.style.display = isReminder ? "" : "none";
        bodyInput.value = "";
    }
    templateSelect.addEventListener("change", applyTemplateMode);
    applyTemplateMode();

    var selectAll = document.getElementById("select-all-projects");
    var projectBoxes = document.querySelectorAll("input[name=\"oids[]\"]");
    selectAll.addEventListener("change", function () {
        projectBoxes.forEach(function (box) { box.checked = selectAll.checked; });
    });
');
?>
<div class="admin-index">
    <h1 class="h4 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">ค้นหาโครงการ</div>
        <div class="card-body">
            <?= Html::beginForm(['email'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small fw-semibold" for="status">สถานะการดำเนินโครงการ</label>
                    <?= Html::dropDownList('status', $statusFilter, [
                        'pending' => 'ยังไม่เสร็จ (ยังไม่เริ่ม / อยู่ระหว่างดำเนินการ)',
                        'all' => 'ทุกสถานะ',
                    ], ['id' => 'status', 'class' => 'form-select']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="start_date">ส่งรายงานล่าสุดตั้งแต่</label>
                    <?= Html::input('date', 'start_date', $startDate, ['id' => 'start_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="end_date">ถึงวันที่</label>
                    <?= Html::input('date', 'end_date', $endDate, ['id' => 'end_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-4 col-md-auto d-flex gap-2">
                    <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('ล้างตัวกรอง', ['email'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
            <p class="text-body-secondary small mb-0 mt-2">
                ช่วงวันที่ (ถ้าระบุ) กรองจากวันที่ส่งรายงานฉบับล่าสุดของแต่ละโครงการ —
                โครงการที่ยังไม่เคยส่งรายงานเลยจะไม่ปรากฏถ้าเลือกช่วงวันที่
            </p>
        </div>
    </div>

    <?= Html::beginForm(['send-email'], 'post', [
        'data-confirm-message' => 'ยืนยันส่งอีเมลนี้?',
    ]) ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">
                รายการส่งถึงโครงการ — พบ <?= count($projects) ?> โครงการตามเงื่อนไขที่ค้นหา
            </div>
            <div class="card-body p-0">
                <?php if (empty($projects)): ?>
                    <p class="text-body-secondary p-3 mb-0">ไม่พบโครงการตามเงื่อนไขที่เลือก</p>
                <?php else: ?>
                    <div style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all-projects" class="form-check-input">
                                </th>
                                <th>เลขที่โครงการ</th>
                                <th>ชื่อโครงการ</th>
                                <th>อีเมล</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="oids[]" value="<?= Html::encode($project['oid']) ?>"
                                               id="oid-<?= Html::encode($project['oid']) ?>" class="form-check-input">
                                    </td>
                                    <td>
                                        <label for="oid-<?= Html::encode($project['oid']) ?>">
                                            <?= Html::encode($projectCodes[$project['oid']] ?? '-') ?>
                                        </label>
                                    </td>
                                    <td><?= Html::encode($project['oname']) ?></td>
                                    <td><?= Html::encode($project['s_email'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">เขียนอีเมล</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="email-template">เทมเพลต</label>
                        <select id="email-template" name="template" class="form-select">
                            <option value="blank">ข่าวสารทั่วไป (เริ่มจากว่าง)</option>
                            <option value="reminder">แจ้งเตือนส่งรายงาน (เทมเพลตสำเร็จรูป)</option>
                        </select>
                        <p class="text-body-secondary small mt-1 mb-0">
                            เลือกเทมเพลตเพื่อกรอกหัวข้อ/เนื้อหาอัตโนมัติ แล้วแก้ไขได้ก่อนส่งจริง
                        </p>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold" for="email-subject">หัวข้อ</label>
                        <?= Html::textInput('subject', '', ['id' => 'email-subject', 'class' => 'form-control', 'required' => true]) ?>
                    </div>
                    <div class="col-md-3" id="deadline-field-wrap" style="display: none;">
                        <label class="form-label small fw-semibold" for="email-deadline">กำหนดส่งรายงานภายในวันที่</label>
                        <?= Html::input('date', 'deadline_date', $defaultDeadline, ['id' => 'email-deadline', 'class' => 'form-control']) ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="email-body" id="email-body-label">เนื้อหา</label>
                        <?= Html::textarea('body', '', ['id' => 'email-body', 'class' => 'form-control', 'rows' => 6, 'required' => true]) ?>
                        <p id="email-body-hint" class="text-body-secondary small mt-1 mb-0" style="display: none;">
                            เนื้อหาหลักของอีเมล (รหัสโครงการ ชื่อหัวหน้าโครงการ ชื่อโครงการ ลิงก์ส่งรายงาน ฯลฯ)
                            จะถูกสร้างขึ้นอัตโนมัติแยกตามแต่ละโครงการที่เลือก — ข้อความในกล่องนี้ (ถ้ามี) จะถูกแนบเพิ่มไว้ท้ายอีเมล
                        </p>
                    </div>
                </div>
                <?= Html::submitButton('ส่งอีเมล', ['class' => 'btn btn-primary mt-3', 'disabled' => empty($projects)]) ?>
            </div>
        </div>
    <?= Html::endForm() ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            ประวัติการแจ้งเตือน / ข่าวสาร (<?= (int) $notificationsProvider->getTotalCount() ?> รายการ)
        </div>
        <div class="card-body border-bottom">
            <?= Html::beginForm(['email'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <?php // เก็บตัวกรอง "ค้นหาโครงการ" เดิมไว้เมื่อ submit ฟอร์มนี้ ไม่ให้ค่าหายไป ?>
                <?= Html::hiddenInput('status', $statusFilter) ?>
                <?= Html::hiddenInput('start_date', $startDate) ?>
                <?= Html::hiddenInput('end_date', $endDate) ?>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="notif_start_date">ส่งตั้งแต่วันที่</label>
                    <?= Html::input('date', 'notif_start_date', $notifStartDate, ['id' => 'notif_start_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="notif_end_date">ถึงวันที่</label>
                    <?= Html::input('date', 'notif_end_date', $notifEndDate, ['id' => 'notif_end_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold" for="notif_search">ค้นหาโครงการ / ผู้รับ</label>
                    <?= Html::textInput('notif_search', $notifSearch, [
                        'id' => 'notif_search',
                        'class' => 'form-control',
                        'placeholder' => 'ชื่อโครงการ หรือ อีเมลผู้รับ',
                    ]) ?>
                </div>
                <div class="col-sm-4 col-md-auto d-flex gap-2">
                    <?= Html::submitButton('กรอง', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('ล้างตัวกรอง', ['email', 'status' => $statusFilter, 'start_date' => $startDate, 'end_date' => $endDate], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <p class="text-body-secondary p-3 mb-0">
                    <?= ($notifStartDate !== '' || $notifEndDate !== '' || $notifSearch !== '')
                        ? 'ไม่พบประวัติการส่งตามเงื่อนไขที่กรอง'
                        : 'ยังไม่มีประวัติการส่ง' ?>
                </p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>วันที่ส่ง</th>
                        <th>โครงการ</th>
                        <th>ผู้รับ</th>
                        <th>ประเภท</th>
                        <th>หัวข้อ</th>
                        <th>สถานะ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?= Html::encode(ThaiDate::format($notification->sent_at)) ?></td>
                            <td>
                                <?= Html::encode($notification->researchProject->oname ?? $notification->oid) ?>
                            </td>
                            <td><?= Html::encode($notification->recipient_email) ?></td>
                            <td><?= Html::encode(ReportNotification::TRIGGER_TYPE_LABELS[$notification->trigger_type] ?? $notification->trigger_type) ?></td>
                            <td><?= Html::encode($notification->subject ?: '-') ?></td>
                            <td>
                                <span class="badge <?= ReportNotification::SENT_STATUS_BADGE_CLASSES[$notification->sent_status] ?? 'bg-secondary' ?>"
                                      <?php if ($notification->error_message): ?>title="<?= Html::encode($notification->error_message) ?>"<?php endif; ?>>
                                    <?= Html::encode(ReportNotification::SENT_STATUS_LABELS[$notification->sent_status] ?? $notification->sent_status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget(['pagination' => $notificationsProvider->pagination]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
