<?php

namespace app\services;

use app\models\ResearchProject;

/**
 * Fetches live project data from ระบบ A's public GET endpoint and upserts it
 * into research_projects by oid. Called on-demand every time a report form
 * is opened (see README.md — "Flow หลัก").
 */
class ProjectSourceService
{
    private const SOURCE_URL = 'https://iacuc.kku.ac.th/offer/detail3/%s/nelac';

    public static function fetchAndUpsert(string $oid): ?ResearchProject
    {
        $url = sprintf(self::SOURCE_URL, $oid);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            return null;
        }

        $items = json_decode($response, true);
        $item = $items[0] ?? null;
        if (empty($item['oid'])) {
            return null;
        }

        $project = ResearchProject::findOne(['oid' => $item['oid']]) ?? new ResearchProject();
        $project->setAttributes([
            'oid'           => $item['oid'],
            'oname'         => $item['oname'] ?? null,
            'oname_en'      => $item['oname_en'] ?? null,
            'm_pro_th'      => $item['m_pro_th'] ?? null,
            'm_pro_en'      => $item['m_pro_en'] ?? null,
            'm_pro_dept_th' => $item['m_pro_dept_th'] ?? null,
            'md_name'       => $item['md_name'] ?? null,
            'meeting_no'    => $item['meeting_no'] ?? null,
            'meeting_date'  => $item['meeting_date'] ?? null,
            's_email'       => $item['s_email'] ?? null,
            's_phone'       => $item['s_phone'] ?? null,
            'raw_json'      => json_encode($item, JSON_UNESCAPED_UNICODE),
        ], false);

        return $project->save() ? $project : null;
    }
}
