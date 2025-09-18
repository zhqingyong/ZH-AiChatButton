<?php

/* AI故障诊断接口
 * 该接口用于接收设备名称、所属产线、故障类别和故障描述，并返回AI生成的维修建议
 * 该接口使用了阿里云的AI服务进行故障诊断，注意该接口文件使用Nginx或者apache等支持HTTPS的服务器环境发布，配好php环境以及需要的扩展，具体的请自行研究
 * 需要在阿里云控制台申请API Key，并替换下面的api Key变量
 * 注意：请确保在生产环境中妥善保管API Key，避免泄露
 * Author: Zhao
 * Date: 2025-06-12
*/

require "../../config.php";
require "../../istoken.php";
header('Content-Type: application/json');

// 获取前端传递的参数
$deviceName = $_POST['deviceName'] ?? '';
$workshop = $_POST['workshop'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';

// 验证必填字段
if (empty($deviceName) || empty($description)) {
    echo json_encode(['code' => 400, 'msg' => '设备名称和故障描述不能为空']);
    exit;
}

// 构建AI提示词
$prompt = "你是一个专业的设备维修诊断专家。请根据以下信息提供维修建议(100字以内):\n\n";
$prompt .= "设备名称: " . $deviceName . "\n";
$prompt .= "所属产线: " . $workshop . "\n";
$prompt .= "故障类别: " . $category . "\n";
$prompt .= "故障描述: " . $description . "\n\n";
$prompt .= "请结合已知信息给出具体的检查步骤和维修建议:";

// 设置请求的URL
$url = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';

$apiKey = 'sk-xxxxxxxxxx'; //这里替换为你自己的阿里云Qwen API Key
if (!$apiKey) {
    die(json_encode(['code' => 500, 'msg' => 'API Key未配置']));
}

// 设置请求头
$headers = [
    'Authorization: Bearer '.$apiKey,
    'Content-Type: application/json'
];

// 设置请求体
$data = [
    "model" => "qwen-plus", // 使用Qwen Plus模型，快速对话能力
    "messages" => [
        [
            "role" => "system",
            "content" => "你是一个专业的工业设备维修诊断专家，能够根据故障描述提供简明扼要的维修建议。回答要专业、准确，控制在100字以内。"
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => 150
];

// 初始化cURL会话
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不验证SSL证书
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15秒超时

// 执行请求
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['code' => 500, 'msg' => 'AI服务请求失败: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// 解析响应
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($result['choices'][0]['message']['content'])) {
    echo json_encode(['code' => 500, 'msg' => 'AI响应解析失败']);
    exit;
}

// 返回成功响应
echo json_encode([
    'code' => 200,
    'msg' => 'success',
    'data' => $result
]);

?>