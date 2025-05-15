<?php
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['status'], $data['person'], $data['datetime'])) {
    // 保存データの準備
    $statusData = [
        "status" => $data['status'],
        "person" => $data['person'],
        "datetime" => $data['datetime']
    ];

    // JSONファイルに保存
    file_put_contents('status.json', json_encode($statusData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // ログファイルに追記
    $statusLabel = $data['status'] === 'open' ? '開錠' : '施錠';
    $logMessage = "{$data['datetime']} に {$data['person']} によって{$statusLabel}されました\n";
    file_put_contents('log.txt', $logMessage, FILE_APPEND | LOCK_EX);

    // メール送信用データ
    $emailData = [
        "to" => "m22237@g.metro-cit.ac.jp",
        "subject" => "開錠/施錠通知",
        "body" => $logMessage
    ];

    // メール送信（非同期にGoogle Apps ScriptへPOST）
    $ch = curl_init("https://script.google.com/macros/s/AKfycbwL3sVWxHpTCDTYPFAaW_I0jD-Phg6NfE-Ws6BXMJI1efXY0U62-046fWe_2cRRrnh-/exec");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 応答を待たずにタイムアウト（疑似非同期）
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(["success" => true]);
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "不正なデータです"]);
}
?>