<?php

/** @var yii\web\View $this */
/** @var app\models\ResearchProject $project */
/** @var app\models\ProgressReport $model */
/** @var app\models\ReportPublication[] $publications */
/** @var app\models\ReportIpFiling[] $ipFilings */
/** @var array $attachmentErrors */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use app\models\ReportPublication;
use app\models\ReportIpFiling;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'แจ้งความก้าวหน้าโครงการ — ' . $project->oid;
$this->params['breadcrumbs'][] = $this->title;

$fields = [
    'oid' => 'รหัสโครงการ (oid)',
    'oname' => 'ชื่อโครงการ (ไทย)',
    'oname_en' => 'ชื่อโครงการ (อังกฤษ)',
    'm_pro_th' => 'หัวหน้าโครงการ',
    'm_pro_dept_th' => 'สังกัด/ภาควิชา',
    'md_name' => 'ผู้ประสานงาน',
    'meeting_no' => 'ครั้งที่ประชุม',
    'meeting_date' => 'วันที่ประชุม',
    's_email' => 'อีเมลผู้ยื่นโครงการ',
    's_phone' => 'เบอร์โทรผู้ยื่นโครงการ',
    'updated_at' => 'ดึงข้อมูลล่าสุดเมื่อ',
];

$raw = $project->getRawData();
$val = static fn (string $key): string => (string) (($raw[$key] ?? '') !== '' ? $raw[$key] : '-');

$animalGroups = $project->getApprovedAnimalGroups();
$primaryMale = $animalGroups['male'];
$primaryFemale = $animalGroups['female'];
$secondMale = $animalGroups['second_male'];
$secondFemale = $animalGroups['second_female'];
$hasSecondGroup = $animalGroups['has_second_group'] || $secondMale !== null || $secondFemale !== null;

$statusOptions = [
    'not_started' => 'ยังไม่เริ่มดำเนินการ',
    'in_progress' => 'อยู่ระหว่างดำเนินการ',
    'completed' => 'ดำเนินการเสร็จสิ้น',
    'terminated_early' => 'ยุติโครงการก่อนกำหนด',
    'cancelled' => 'ยกเลิกโครงการ',
];
$yesNo = ['yes' => 'ใช่', 'no' => 'ไม่ใช่'];

// เติม ?v=<เวลาแก้ไขไฟล์ล่าสุด> ต่อท้าย URL ของไฟล์ JS/CSS ของโปรเจกต์เอง (ไม่ใช่ vendor) กัน
// browser/reverse-proxy บนเซิร์ฟเวอร์แคชไฟล์เก่าค้างไว้หลัง deploy โค้ดใหม่ — ก่อนหน้านี้ registerJsFile
// ใช้ path ตรงๆ ไม่มี query string เลย ทำให้ browser ที่เคยโหลดหน้านี้ไปแล้วไม่รู้ว่าไฟล์เปลี่ยน
// (นี่คือสาเหตุที่ทำให้ผู้ใช้บนเซิร์ฟเวอร์เห็นเลข 6.1.1/6.2.1 ไม่เพิ่มขึ้นทั้งที่โค้ดฝั่งเซิร์ฟเวอร์อัปเดตแล้ว)
$assetVersion = static function (string $relativePath): string {
    $path = Yii::getAlias('@webroot/' . ltrim($relativePath, '/'));
    return is_file($path) ? ('?v=' . filemtime($path)) : '';
};

$this->registerCssFile('@web/css/vendor/flatpickr.min.css');
$this->registerJsFile('@web/js/report-form.js' . $assetVersion('js/report-form.js'), ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/dynamic-rows.js' . $assetVersion('js/dynamic-rows.js'));
$this->registerJsFile('@web/js/animal-usage-warning.js' . $assetVersion('js/animal-usage-warning.js'));
$this->registerJsFile('@web/js/vendor/flatpickr.min.js');
$this->registerJsFile('@web/js/thai-date-input.js' . $assetVersion('js/thai-date-input.js'));
?>
<div class="report-create">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-4">
        เข้าสู่ระบบในนาม <strong><?= Html::encode(Yii::$app->session->get('sso_email')) ?></strong>
        — ข้อมูลโครงการได้รับจากระบบ (<code>iacuc.kku.ac.th</code>) และบันทึกลงฐานข้อมูลของเราแล้ว
    </p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <?php foreach ($fields as $attribute => $label): ?>
                    <tr>
                        <th class="text-body-secondary fw-normal" style="width: 240px;"><?= Html::encode($label) ?></th>
                        <td><?= Html::encode($project->$attribute ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">ข้อมูลสัตว์ที่ได้รับการอนุมัติ</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <tr>
                    <th class="text-body-secondary fw-normal" style="width: 240px;">ชนิดสัตว์</th>
                    <td>
                        <?= Html::encode($val('an_type')) ?>
                        <?php if (!empty($raw['an_type_other'])): ?>
                            (<?= Html::encode($raw['an_type_other']) ?>)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">สายพันธุ์ / ลักษณะ</th>
                    <td><?= Html::encode($val('an_name')) ?> — <?= Html::encode($val('an_sectment')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้)</th>
                    <td><?= Html::encode($primaryMale ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย)</th>
                    <td><?= Html::encode($primaryFemale ?? '-') ?></td>
                </tr>
                <?php if ($hasSecondGroup): ?>
                    <tr>
                        <th class="text-body-secondary fw-normal">กลุ่มสัตว์ที่สอง</th>
                        <td><?= Html::encode($val('h_name')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้ — กลุ่มที่สอง)</th>
                        <td><?= Html::encode($secondMale ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย — กลุ่มที่สอง)</th>
                        <td><?= Html::encode($secondFemale ?? '-') ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th class="text-body-secondary fw-normal">แหล่งที่มาของสัตว์</th>
                    <td><?= Html::encode($val('an_resource')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">สถานที่เลี้ยง</th>
                    <td><?= Html::encode($val('an_location')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">ช่วงเวลาที่ใช้สัตว์</th>
                    <td><?= Html::encode($val('start_ani')) ?> — <?= Html::encode($val('stop_ani')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">หมายเหตุ</th>
                    <td><?= Html::encode($val('an_ex')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">มติที่ประชุม / สถานะอนุมัติ</th>
                    <td><?= Html::encode($val('meet_note')) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info small">
        แบบฟอร์มนี้ใช้สำหรับรายงานความก้าวหน้าโครงการและแจ้งปิดโครงการเท่านั้น หากต้องการแก้ไขข้อมูลโครงการหรือขออนุมัติเปลี่ยนแปลงวัตถุประสงค์/วิธีการทดลอง/จำนวนสัตว์ที่ขออนุมัติ กรุณาติดต่อเจ้าหน้าที่ <strong>สำนักงานคณะกรรมการจริยธรรมการใช้สัตว์ในงานวิจัย</strong> 
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">แบบรายงานความก้าวหน้า / แจ้งปิดโครงการ</div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'progress-report-form',
                'options' => ['enctype' => 'multipart/form-data'],
            ]); ?>

            <h2 class="h6 fw-bold mt-2 mb-3">ข้อมูลโครงการ</h2>
            <?= $form->field($model, 'project_code')->textInput(['maxlength' => 50,'readonly' => true,'class'=>'form-control bg-body-secondary'])->label('ข้อ 1: รหัสโครงการ') ?>
            <?= $form->field($model, 'meeting_ref')->textInput(['maxlength' => 255,'readonly' => true,'class'=>'form-control bg-body-secondary'])->label('ข้อ 2: เข้าประชุมครั้งที่ / วันที่พิจารณา') ?>
            <?= $form->field($model, 'pi_name')->textInput(['maxlength' => 255,'readonly' => true,'class'=>'form-control bg-body-secondary'])->label('ข้อ 3: ชื่อหัวหน้าโครงการ') ?>
            <?= $form->field($model, 'project_name_th')->textarea(['rows' => 2,'readonly' => true,'class'=>'form-control bg-body-secondary'])->label('ข้อ 4: ชื่อโครงการ (ภาษาไทย)') ?>
            <?= $form->field($model, 'project_name_en')->textarea(['rows' => 2,'readonly' => true,'class'=>'form-control bg-body-secondary'])->label('ข้อ 5: ชื่อโครงการ (ภาษาอังกฤษ)') ?>

            <h2 class="h6 fw-bold mt-2 mb-3">รายงานความก้าวหน้าโครงการ</h2>
            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 1 — วัตถุประสงค์โครงการ</h2>
            <?= $form->field($model, 'objective_changed')->radioList(
                ['same' => 'ตามที่ระบุในข้อเสนอฯ ที่ผ่านการรับรอง', 'changed' => 'มีการเปลี่ยนแปลงวัตถุประสงค์'],
                ['id' => false]
            )->label('มีการเปลี่ยนแปลงวัตถุประสงค์ของโครงการหรือไม่') ?>
            <div data-show-when="objective_changed:changed">
                <?= $form->field($model, 'objective_change_detail')->textarea(['rows' => 3])->label('รายละเอียดการเปลี่ยนแปลงวัตถุประสงค์') ?>
            </div>

            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 2.1 — สถานะการดำเนินโครงการ</h2>
            <?= $form->field($model, 'status')->dropDownList($statusOptions, ['prompt' => '— เลือกสถานะ —']) ?>
            <div data-show-when="status:not_started">
                <?= $form->field($model, 'expected_start_date')->textInput([
                    'class' => 'form-control thai-date-input',
                    'placeholder' => 'วว/ดด/ปปปป เช่น 01/12/2569',
                    'value' => ThaiDate::toThaiInput($model->expected_start_date),
                    'maxlength' => 10,
                ])->label('วันที่คาดว่าจะเริ่มดำเนินการ') ?>
            </div>
            <div data-show-when="status:in_progress">
                <?= $form->field($model, 'expected_complete_date')->textInput([
                    'class' => 'form-control thai-date-input',
                    'placeholder' => 'วว/ดด/ปปปป เช่น 01/12/2569',
                    'value' => ThaiDate::toThaiInput($model->expected_complete_date),
                    'maxlength' => 10,
                ])->label('วันที่คาดว่าจะเสร็จสิ้น') ?>
            </div>
            <div data-show-when="status:completed">
                <?= $form->field($model, 'completed_date')->textInput([
                    'class' => 'form-control thai-date-input',
                    'placeholder' => 'วว/ดด/ปปปป เช่น 01/12/2569',
                    'value' => ThaiDate::toThaiInput($model->completed_date),
                    'maxlength' => 10,
                ])->label('วันที่ดำเนินการเสร็จสิ้น') ?>
            </div>
            <div data-show-when="status:not_started,terminated_early,cancelled">
                <?= $form->field($model, 'stop_reason')->textarea(['rows' => 3])->label('เหตุผลที่ยังไม่เริ่มดำเนินการ/ยุติ/ยกเลิกโครงการ') ?>
            </div>

            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 3 — การใช้สัตว์ทดลอง</h2>

            <p class="fw-semibold small mb-2">ข้อ 3.1: จำนวนสัตว์ที่ขอ/ได้รับอนุมัติ (ดึงจากข้อมูลที่อนุมัติด้านบนโดยตรง แก้ไขไม่ได้)</p>
            <div class="row">
                <div class="col-sm-6">
                    <?= $form->field($model, 'animal_requested_male')->input('number', ['min' => 0, 'readonly' => true, 'tabindex' => -1, 'class' => 'form-control bg-body-secondary'])->label('จำนวนตัวผู้') ?>
                </div>
                <div class="col-sm-6">
                    <?= $form->field($model, 'animal_requested_female')->input('number', ['min' => 0, 'readonly' => true, 'tabindex' => -1, 'class' => 'form-control bg-body-secondary'])->label('จำนวนตัวเมีย') ?>
                </div>
            </div>
            <?= $form->field($model, 'animal_requested_note')->textarea(['rows' => 3, 'readonly' => true, 'tabindex' => -1, 'class' => 'form-control bg-body-secondary'])->label('หมายเหตุ (ชนิด/สายพันธุ์/กลุ่มเพิ่มเติม)') ?>

            <p class="fw-semibold small mb-2 mt-3">ข้อ 3.2: จำนวนสัตว์ที่ใช้จริง</p>
            <div class="row">
                <div class="col-sm-4">
                    <?= $form->field($model, 'animal_used_male')->input('number', ['min' => 0])->label('จำนวนตัวผู้') ?>
                </div>
                <div class="col-sm-4">
                    <?= $form->field($model, 'animal_used_female')->input('number', ['min' => 0])->label('จำนวนตัวเมีย') ?>
                </div>
                <div class="col-sm-4">
                    <?= $form->field($model, 'animal_used_unit')->dropDownList(ProgressReport::UNIT_LABELS)->label('หน่วย') ?>
                </div>
            </div>
            <div id="animal-usage-warning" class="alert alert-warning d-none" role="alert">
                <i class="fas fa-triangle-exclamation me-1"></i>
                จำนวนสัตว์ที่ใช้จริงเกินกว่าจำนวนที่ได้รับอนุมัติ กรุณาตรวจสอบความถูกต้อง (ยังคงกรอกและบันทึกข้อมูลต่อได้)
            </div>
            <?= $form->field($model, 'animal_used_note')->textarea(['rows' => 2])->label('หมายเหตุ') ?>

            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 4 — การเปลี่ยนแปลงระหว่างดำเนินโครงการ</h2>
            <?= $form->field($model, 'method_changed')->radioList($yesNo, ['id' => false])->label('ข้อ 4.1: มีการเปลี่ยนแปลงวิธีการทดลองหรือไม่') ?>
            <div data-show-when="method_changed:yes">
                <?= $form->field($model, 'method_change_detail')->textarea(['rows' => 3])->label('รายละเอียดการเปลี่ยนแปลงวิธีการทดลอง') ?>
            </div>

            <?= $form->field($model, 'post_study_handling')->dropDownList(
                ProgressReport::POST_STUDY_HANDLING_LABELS
            )->label('ข้อ 4.2: การปฏิบัติต่อสัตว์หลังจากเสร็จสิ้นโครงการ') ?>
            <div data-show-when="post_study_handling:changed">
                <?= $form->field($model, 'post_study_handling_detail')->textarea(['rows' => 3])
                    ->label('มีการเปลี่ยนแปลงวิธีปฏิบัติต่อสัตว์ (ระบุโดยละเอียด)') ?>
            </div>

            <?= $form->field($model, 'adverse_event')->radioList($yesNo, ['id' => false])->label('ข้อ 4.3: มีเหตุการณ์ไม่พึงประสงค์เกิดขึ้นหรือไม่') ?>
            <div data-show-when="adverse_event:yes">
                <?= $form->field($model, 'adverse_event_detail')->textarea(['rows' => 3])->label('รายละเอียดเหตุการณ์ไม่พึงประสงค์') ?>
            </div>

            <?= $form->field($model, 'personnel_changed')->radioList($yesNo, ['id' => false])->label('ข้อ 4.4: มีการเปลี่ยนแปลงผู้ปฏิบัติงานหรือไม่') ?>
            <div data-show-when="personnel_changed:yes">
                <?= $form->field($model, 'personnel_change_detail')->textarea(['rows' => 3])->label('รายละเอียดการเปลี่ยนแปลงผู้ปฏิบัติงาน') ?>
            </div>

            <?= $form->field($model, 'alt_method_found')->radioList($yesNo, ['id' => false])->label('ข้อ 4.5: พบวิธีการทดแทนการใช้สัตว์ทดลองหรือไม่') ?>
            <div data-show-when="alt_method_found:yes">
                <?= $form->field($model, 'alt_method_detail')->textarea(['rows' => 3])->label('รายละเอียดวิธีการทดแทน') ?>
            </div>

            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 5 — สรุปผลการดำเนินงาน</h2>
            <?= $form->field($model, 'study_summary')->textarea(['rows' => 4])->label(false) ?>

            <h2 class="h6 fw-bold mt-4 mb-3">ข้อ 6 — การตีพิมพ์ เผยแพร่ หรือนำไปใช้ประโยชน์</h2>
            <?= $form->field($model, 'has_publication')->radioList(
                ['no' => 'ไม่มีการตีพิมพ์เผยแพร่', 'yes' => 'มีการตีพิมพ์เผยแพร่'],
                ['id' => false]
            )->label(false) ?>

            <div data-show-when="has_publication:yes">
                <h3 class="h6 fw-semibold mt-4 mb-1">ข้อมูลการตีพิมพ์ เผยแพร่</h3>

                <h4 class="small fw-bold text-uppercase text-body-secondary mt-3 mb-2">6.1 กรณีตีพิมพ์เผยแพร่ในวารสารวิชาการ</h4>
                <div id="publications-list" data-number-prefix="6.1.">
                    <?php foreach ($publications as $i => $pub): ?>
                        <div class="dynamic-row border rounded p-3 mb-3" data-index="<?= $i ?>">
                            <div class="fw-bold text-primary mb-2">ผลงานที่ 6.1.<span class="row-number"><?= $i + 1 ?></span></div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <?= $form->field($pub, "[$i]article_title")->textarea(['rows' => 2])
                                        ->label('ผลผลิต: บทความวิจัย ระบุชื่อบทความ') ?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($pub, "[$i]level")
                                        ->dropDownList(ReportPublication::LEVEL_LABELS, ['prompt' => '— ระดับ —']) ?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($pub, "[$i]journal_name")->textInput()
                                        ->label('ตีพิมพ์ในวารสาร ระบุชื่อวารสาร') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]issue")->textInput()->label('ฉบับที่') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]page")->textInput()->label('หน้าที่') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]pub_month")->textInput()->label('เดือน') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]pub_year")->textInput()->label('ปี') ?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($pub, "[$i]doi")->textInput()->label('DOI') ?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($pub, "[$i]db_type")
                                        ->dropDownList(ReportPublication::DB_TYPE_LABELS, ['prompt' => '— อยู่ในฐานข้อมูล —']) ?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($pub, "[$i]db_other")->textInput()->label('ระบุฐานข้อมูลอื่นๆ (ถ้ามี)') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]quartile")->textInput()->label('Quartile / กลุ่มที่') ?>
                                </div>
                                <div class="col-sm-3">
                                    <?= $form->field($pub, "[$i]impact_factor")->textInput()->label('Impact Factor') ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">แนบไฟล์ PDF (ถ้ามี)</label>
                                    <input type="file" name="ReportPublication[<?= $i ?>][pdf_file]" accept="application/pdf" class="form-control">
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm mt-2" data-row-remove>ลบรายการนี้</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-4" data-row-add="publications-list">
                    + เพิ่มผลงานตีพิมพ์
                </button>

                <h4 class="small fw-bold text-uppercase text-body-secondary mt-2 mb-2">6.2 การยื่นจดทรัพย์สินทางปัญญา</h4>
                <p class="text-body-secondary small">หากท่านตีพิมพ์ผลงานหรือยื่นจดทรัพย์สินทางปัญญามากกว่า 1 เรื่องจากโครงการนี้ กรุณาระบุรายละเอียดของผลงานทั้งหมด</p>
                <div id="ip-filings-list" data-number-prefix="6.2.">
                    <?php foreach ($ipFilings as $i => $ip): ?>
                        <div class="dynamic-row border rounded p-3 mb-3" data-index="<?= $i ?>">
                            <div class="fw-bold text-primary mb-2">รายการที่ 6.2.<span class="row-number"><?= $i + 1 ?></span></div>
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <?= $form->field($ip, "[$i]ip_type")
                                        ->dropDownList(ReportIpFiling::IP_TYPE_LABELS, ['prompt' => '— ประเภท —']) ?>
                                </div>
                                <div class="col-sm-4">
                                    <?= $form->field($ip, "[$i]filed_date")->textInput([
                                        'class' => 'form-control thai-date-input',
                                        'placeholder' => 'วว/ดด/ปปปป เช่น 01/12/2569',
                                        'value' => ThaiDate::toThaiInput($ip->filed_date),
                                        'maxlength' => 10,
                                    ])->label('วันที่ยื่นจด') ?>
                                </div>
                                <div class="col-sm-4">
                                    <?= $form->field($ip, "[$i]registration_no")->textInput()
                                        ->label('เลขที่จดทะเบียน') ?>
                                </div>
                                <div class="col-12">
                                    <?= $form->field($ip, "[$i]asset_name")->textInput()->label('ชื่อทรัพย์สิน') ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">แนบไฟล์ PDF (ถ้ามี)</label>
                                    <input type="file" name="ReportIpFiling[<?= $i ?>][pdf_file]" accept="application/pdf" class="form-control">
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm mt-2" data-row-remove>ลบรายการนี้</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-row-add="ip-filings-list">
                    + เพิ่มรายการทรัพย์สินทางปัญญา
                </button>
                <p class="text-body-secondary small mt-2">หากส่งฟอร์มไม่ผ่านต้องเลือกไฟล์ PDF ที่แนบไว้ในแต่ละรายการใหม่อีกครั้ง (ข้อจำกัดของเบราว์เซอร์)</p>
            </div>

            <h2 class="h6 fw-bold mt-4 mb-3">เอกสารแนบ (PDF)</h2>
            <?php if (!empty($attachmentErrors)): ?>
                <div class="alert alert-danger py-2">
                    <?php foreach ($attachmentErrors as $error): ?>
                        <div><?= Html::encode($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <input type="file" name="attachments[]" multiple accept="application/pdf" class="form-control">
                <div class="form-text">แนบไฟล์ PDF ได้หลายไฟล์ (ไม่เกิน 10 ไฟล์ ไฟล์ละไม่เกิน 10MB) — ถ้าฟอร์มส่งไม่ผ่านต้องเลือกไฟล์แนบใหม่อีกครั้ง (ข้อจำกัดของเบราว์เซอร์)</div>
            </div>

            <div class="d-grid mt-4">
                <?= Html::submitButton('ส่งรายงาน', ['class' => 'btn btn-primary btn-lg']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
