<?php

/** @var yii\web\View $this */
/** @var array $projects */
/** @var string $startDate */
/** @var string $endDate */
/** @var string $search */
/** @var app\models\ResearchProject[] $projectsWithoutReports */
/** @var yii\data\Pagination $pagination */
/** @var yii\data\ActiveDataProvider $projectsWithoutReportsProvider */

use app\helpers\ThaiDate;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;

$this->title = 'รายการโครงการที่ส่งรายงาน';
$this->params['breadcrumbs'][] = $this->title;

$statusLabels = [
    'not_started' => 'ยังไม่เริ่มดำเนินการ',
    'in_progress' => 'อยู่ระหว่างดำเนินการ',
    'completed' => 'ดำเนินการเสร็จสิ้น',
    'terminated_early' => 'ยุติโครงการก่อนกำหนด',
    'cancelled' => 'ยกเลิกโครงการ',
];
$statusBadgeClasses = [
    'not_started' => 'bg-secondary',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'terminated_early' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
];
?>
<div class="report-index">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-4">
        แสดงโครงการที่มีการส่งรายงานความก้าวหน้าในช่วงวันที่เลือก (ค่าเริ่มต้น: เดือนนี้)
    </p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">เพิ่มโครงการเข้าระบบ</div>
        <div class="card-body">
            <p class="text-body-secondary small mb-2">
                สำหรับโครงการที่ได้รับอนุมัติแล้ว แต่ยังไม่มีใครเปิดหน้า "แจ้งความก้าวหน้าโครงการ" เลยสักครั้ง
                (จึงยังไม่มีข้อมูลอยู่ในระบบนี้ ค้นหา/ส่งอีเมลแจ้งเตือนไม่ได้) — กรอกรหัส oid เพื่อดึงข้อมูลโครงการ
                จากระบบ A เข้ามาก่อน โดยยังไม่ต้องบันทึกรายงานความก้าวหน้าฉบับแรก
            </p>
            <?= Html::beginForm(['/report/fetch'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small fw-semibold" for="fetch-oid">รหัส oid</label>
                    <?= Html::textInput('oid', '', [
                        'id' => 'fetch-oid',
                        'class' => 'form-control',
                        'required' => true,
                        'placeholder' => 'เช่น 123',
                    ]) ?>
                </div>
                <div class="col-sm-4 col-md-auto">
                    <?= Html::submitButton('ดึงข้อมูลโครงการ', ['class' => 'btn btn-outline-primary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold" for="search">ค้นหาชื่อโครงการ / รหัสโครงการ</label>
                    <?= Html::textInput('search', $search, [
                        'id' => 'search',
                        'class' => 'form-control',
                        'placeholder' => 'เช่น แผ่นแปะ หรือ จส.มข. 7/61',
                    ]) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="start_date">ตั้งแต่วันที่</label>
                    <?= Html::input('date', 'start_date', $startDate, ['id' => 'start_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="end_date">ถึงวันที่</label>
                    <?= Html::input('date', 'end_date', $endDate, ['id' => 'end_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-4 col-md-auto d-flex gap-2">
                    <?= Html::submitButton('กรอง', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('ล้างตัวกรอง', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            พบ <?= (int) $pagination->totalCount ?> โครงการ
            (<?= Html::encode(ThaiDate::format($startDate, false)) ?> — <?= Html::encode(ThaiDate::format($endDate, false)) ?>)
            <?php if ($search !== ''): ?>
                — ค้นหา "<?= Html::encode($search) ?>"
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($projects)): ?>
                <p class="text-body-secondary p-3 mb-0">ไม่พบโครงการที่ส่งรายงานในช่วงวันที่เลือก</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>หัวหน้าโครงการ</th>
                        <th>สถานะการดำเนินโครงการ</th>
                        <th>จำนวนรายงานในช่วงนี้</th>
                        <th>ส่งล่าสุดเมื่อ</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $project): ?>
                        <?php $status = $project['latest_status']; ?>
                        <tr>
                            <td><?= Html::encode($project['project_code'] ?: '-') ?></td>
                            <td><?= Html::encode($project['oname']) ?></td>
                            <td><?= Html::encode($project['m_pro_th']) ?></td>
                            <td>
                                <span class="badge <?= $statusBadgeClasses[$status] ?? 'bg-secondary' ?>">
                                    <?= Html::encode($statusLabels[$status] ?? $status ?? '-') ?>
                                </span>
                            </td>
                            <td><?= (int) $project['report_count'] ?></td>
                            <td><?= Html::encode(ThaiDate::format($project['latest_submitted_at'])) ?></td>
                            <td class="text-end">
                                <?= Html::a('ดูประวัติทั้งหมด', ['oid', 'oid' => $project['oid']], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget(['pagination' => $pagination]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent fw-semibold">
            โครงการที่ยังไม่มีรายงาน (<?= (int) $projectsWithoutReportsProvider->getTotalCount() ?> โครงการ)
        </div>
        <div class="card-body p-0">
            <?php if (empty($projectsWithoutReports)): ?>
                <p class="text-body-secondary p-3 mb-0">ไม่มีโครงการที่ยังไม่มีรายงาน — ทุกโครงการมีการส่งรายงานแล้วอย่างน้อย 1 ฉบับ</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>หัวหน้าโครงการ</th>
                        <th>อีเมลผู้ยื่นโครงการ</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projectsWithoutReports as $project): ?>
                        <tr>
                            <td><?= Html::encode($project->getProjectCode() ?: '-') ?></td>
                            <td><?= Html::encode($project->oname) ?></td>
                            <td><?= Html::encode($project->m_pro_th) ?></td>
                            <td><?= Html::encode($project->s_email ?: '-') ?></td>
                            <td class="text-end">
                                <?= Html::a('ดูรายละเอียดโครงการ', ['oid', 'oid' => $project->oid], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget(['pagination' => $projectsWithoutReportsProvider->pagination]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
