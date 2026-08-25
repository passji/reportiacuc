<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'วัตถุประสงค์ของการรายงานความก้าวหน้าโครงการ';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'วัตถุประสงค์ของการรายงานความก้าวหน้าโครงการเลี้ยงและใช้สัตว์เพื่องานทางวิทยาศาสตร์';

$objectives = [
    [
        'icon' => 'fa-heart-pulse',
        'title' => 'เพื่อติดตามสวัสดิภาพสัตว์ตามหลัก 3Rs',
        'detail' => 'เพื่อให้มั่นใจว่าการดำเนินโครงการเป็นไปตามหลักการ Replacement, Reduction และ Refinement '
            . 'อย่างต่อเนื่อง รวมถึงสามารถติดตามสภาวะความเป็นอยู่ สุขภาพ และสวัสดิภาพของสัตว์ทดลองตลอดระยะเวลา'
            . 'การดำเนินโครงการ',
    ],
    [
        'icon' => 'fa-clipboard-check',
        'title' => 'เพื่อตรวจสอบความสอดคล้องกับโครงร่างการทดลองที่ได้รับอนุมัติ',
        'detail' => 'เพื่อยืนยันว่าการดำเนินงานจริงเป็นไปตามแผนงาน ขั้นตอน วิธีการ และจำนวนสัตว์ที่ระบุไว้ในโครงร่าง'
            . 'การทดลองที่ผ่านการพิจารณาและได้รับอนุมัติจากคณะกรรมการกำกับดูแลการเลี้ยงและใช้สัตว์เพื่องานทาง'
            . 'วิทยาศาสตร์ (IACUC) หากมีการเปลี่ยนแปลงจากแผนเดิมจะได้รับทราบและพิจารณาความเหมาะสมได้ทันท่วงที',
    ],
    [
        'icon' => 'fa-chart-line',
        'title' => 'เพื่อประเมินผลสัมฤทธิ์พร้อมปัญหาอุปสรรคในการดำเนินงาน',
        'detail' => 'เพื่อสรุปความก้าวหน้าของโครงการเทียบกับเป้าหมายที่กำหนดไว้ ตลอดจนรวบรวมปัญหา อุปสรรค หรือ'
            . 'ข้อจำกัดที่พบระหว่างการดำเนินงาน สำหรับใช้เป็นข้อมูลประกอบการพิจารณาแนวทางแก้ไข ปรับปรุง หรือ'
            . 'สนับสนุนการดำเนินโครงการให้บรรลุผลสำเร็จตามที่วางแผนไว้',
    ],
];

// รูปผู้ดูแลระบบ (ยังไม่ได้ใส่ไฟล์จริง) — วางไฟล์ที่ web/images/admins/<photo> แล้วรูปจะขึ้นแทนไอคอน
// placeholder โดยอัตโนมัติ (เช็คว่าไฟล์มีอยู่จริงก่อนถึงจะใช้ <img>)
$adminContacts = [
    [
        'photo' => 'nattarada.png',
        'name' => 'นางณัฐรดา เวทีวุฒาจารย์',
        'positions' => ['นักจัดการงานทั่วไป', 'หัวหน้างานบริหาร'],
        'department' => 'ศูนย์สัตว์ทดลองภาคตะวันออกเฉียงเหนือ',
    ],
    [
        'photo' => 'phatthira.png',
        'name' => 'นางสาวภัทธิรา ดีนอก',
        'positions' => ['นักจัดการงานทั่วไป ชำนาญการ'],
        'department' => 'ศูนย์สัตว์ทดลองภาคตะวันออกเฉียงเหนือ',
    ],
];
?>
<div class="site-about">
    <div class="mx-auto" style="max-width: 860px;">
        <h1 class="h3 fw-bold mb-2"><?= Html::encode($this->title) ?></h1>
        <p class="text-body-secondary mb-4">
            การรายงานความก้าวหน้าโครงการเลี้ยงและใช้สัตว์เพื่องานทางวิทยาศาสตร์ มีวัตถุประสงค์ดังนี้
        </p>

        <div class="d-flex flex-column gap-3 mb-4">
            <?php foreach ($objectives as $i => $item): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex gap-3">
                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-bold"
                             style="width: 2.75rem; height: 2.75rem; font-size: 1.1rem;">
                            <?= $i + 1 ?>
                        </div>
                        <div>
                            <h2 class="h6 fw-bold mb-2 d-flex align-items-center gap-2">
                                <i class="fas <?= $item['icon'] ?> text-primary"></i>
                                <?= Html::encode($item['title']) ?>
                            </h2>
                            <p class="text-body-secondary mb-0"><?= Html::encode($item['detail']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="h5 fw-bold mb-3">ผู้ดูแลระบบ</h2>
        <div class="row g-3 mb-4">
            <?php foreach ($adminContacts as $contact): ?>
                <?php $photoPath = Yii::getAlias('@webroot/images/admins/' . $contact['photo']); ?>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex gap-3 align-items-center">
                            <?php if (is_file($photoPath)): ?>
                                <?= Html::img('@web/images/admins/' . $contact['photo'], [
                                    'class' => 'rounded-circle flex-shrink-0',
                                    'style' => 'width: 4rem; height: 4rem; object-fit: cover;',
                                    'alt' => $contact['name'],
                                ]) ?>
                            <?php else: ?>
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary"
                                     style="width: 4rem; height: 4rem; font-size: 1.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= Html::encode($contact['name']) ?></div>
                                <?php foreach ($contact['positions'] as $position): ?>
                                    <div class="text-body-secondary small"><?= Html::encode($position) ?></div>
                                <?php endforeach; ?>
                                <div class="text-body-secondary small"><?= Html::encode($contact['department']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

     
    </div>
</div>
