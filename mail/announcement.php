<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\ResearchProject $project */
/** @var string $subject */
/** @var string $body */

$this->title = $subject;
?>
<p>เรียน <?= Html::encode($project->m_pro_th ?: 'หัวหน้าโครงการ') ?></p>
<p style="color:#6c757d;font-size:13px;">
    เกี่ยวกับโครงการ: <?= Html::encode($project->oname) ?> (<?= Html::encode($project->getProjectCode() ?: '-') ?>)
</p>
<div style="margin:20px 0;padding:16px;background-color:#f8f9fa;border-radius:6px;">
    <?= nl2br(Html::encode($body)) ?>
</div>
