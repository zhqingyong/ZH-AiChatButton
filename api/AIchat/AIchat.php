<?php
/* AI智能对话接口 - 简洁优化版
 * 适配工业设备维修场景,Api key请替换为你的实际值，apikey需要去阿里云申请，限时免费
 * Author: Zhao Qingyong
 * Date: 2025-07-30
 */

require "../config.php";
require "../istoken.php";
header('Content-Type: application/json');

// 获取前端传递的参数
$message = $_POST['message'] ?? '';
$chatHistory = json_decode($_POST['chatHistory'] ?? '[]', true);
$context = json_decode($_POST['context'] ?? '{}', true);

// 验证必填字段
if (empty($message)) {
    echo json_encode(['code' => 400, 'msg' => '消息内容不能为空']);
    exit;
}

// 生成系统提示词
function generateSystemPrompt($context) {
    $pageType = $context['pageContext']['pageType'] ?? 'general';
    
    $prompts = [
        'index' => '你是一个专业的工业设备管理AI助手，专注于设备故障诊断、维修指导、报修流程和设备管理。',
        'data' => '你是一个专业的数据分析AI助手，专注于设备数据分析、趋势预测、异常检测和业务洞察。',
        'me' => '你是一个系统使用AI助手，专注于功能介绍、操作指导、账户管理和系统帮助。',
        'general' => '你是一个工业设备管理AI助手，能够处理设备维修、数据分析和系统使用等各类问题。'
    ];
    
    $systemPrompt = $prompts[$pageType] ?? $prompts['general'];
    
    // 添加用户上下文
    if (isset($context['deviceInfo'])) {
        $workshop = $context['deviceInfo']['workshop'] ?? '';
        $userRole = $context['deviceInfo']['userRole'] ?? '';
        $userName = $context['deviceInfo']['userName'] ?? '';
        
        if ($workshop || $userRole || $userName) {
            $systemPrompt .= "\n\n当前用户：{$userName}，角色：{$userRole}，工作区域：{$workshop}";
        }
    }
    
    $systemPrompt .= "\n\n请提供简洁专业的回答，重点突出实用性和安全性，一般控制在150字以内。";
    
    return $systemPrompt;
}

// 检查是否为紧急情况
function isEmergency($message) {
    $keywords = ['紧急', '故障', '停机', '报警', '异常', '事故', '危险', '烧毁', '漏电', '爆炸'];
    foreach ($keywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// 主处理逻辑
try {
    // 生成系统提示词
    $systemPrompt = generateSystemPrompt($context);

    // 处理聊天历史（保留最近10条）
    $recentHistory = array_slice($chatHistory, -10);
    
    // 构建消息数组
    $messages = [
        ["role" => "system", "content" => $systemPrompt]
    ];
    
    // 添加历史记录
    foreach ($recentHistory as $item) {
        $messages[] = [
            "role" => $item['type'] === 'user' ? 'user' : 'assistant',
            "content" => $item['content']
        ];
    }
    
    // 添加当前消息
    $messages[] = [
        "role" => "user",
        "content" => $message
    ];
    
    // 设置AI参数
    $emergency = isEmergency($message);
    $aiParams = [
        'model' => 'qwen-plus', // 使用Qwen Plus模型
        'temperature' => $emergency ? 0.3 : 0.7,
        'max_tokens' => $emergency ? 350 : 250,
        'messages' => $messages
    ];
    
    // API请求
    $url = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
    $apiKey = 'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // 替换为你的API Key
    
    if (!$apiKey) {
        throw new Exception('API Key未配置');
    }
    
    // 记录请求开始时间
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aiParams, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 35); // 增加到35秒
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // 连接超时15秒
    curl_setopt($ch, CURLOPT_USERAGENT, 'EMS-AI-Chat/1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    
    // 记录请求结束时间
    $endTime = microtime(true);
    $requestTime = round(($endTime - $startTime) * 1000); // 毫秒
    
    curl_close($ch);
    
    // 详细的错误诊断
    if ($curlErrno !== 0) {
        $errorDetail = "cURL错误码: {$curlErrno}, 错误信息: {$curlError}, 请求时长: {$requestTime}ms";
        
        // 根据具体错误码提供解决方案
        switch ($curlErrno) {
            case 28: // CURLE_OPERATION_TIMEDOUT
                throw new Exception("请求超时({$requestTime}ms) - 建议检查网络连接或API服务状态");
            case 6: // CURLE_COULDNT_RESOLVE_HOST
                throw new Exception("DNS解析失败 - 请检查网络连接和DNS设置");
            case 7: // CURLE_COULDNT_CONNECT
                throw new Exception("无法连接到AI服务 - 请检查网络防火墙设置");
            case 35: // CURLE_SSL_CONNECT_ERROR
                throw new Exception("SSL连接错误 - 请检查HTTPS配置");
            default:
                throw new Exception("网络请求失败: {$errorDetail}");
        }
    }
    
    // 检查HTTP状态码
    if ($httpCode !== 200) {
        throw new Exception("AI服务响应异常 (HTTP {$httpCode}) - 请检查API Key或稍后重试");
    }
    
    if (empty($response)) {
        throw new Exception("收到空响应 - 请求时长: {$requestTime}ms");
    }
    
    // 解析响应
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('响应格式错误: ' . json_last_error_msg() . " - 原始响应: " . substr($response, 0, 200));
    }
    
    if (!isset($result['choices'][0]['message']['content'])) {
        if (isset($result['error'])) {
            $errorMsg = $result['error']['message'] ?? '未知AI服务错误';
            $errorCode = $result['error']['code'] ?? 'unknown';
            throw new Exception("AI服务错误 ({$errorCode}): {$errorMsg}");
        }
        throw new Exception('AI响应格式异常 - 响应: ' . substr($response, 0, 200));
    }
    
    $aiReply = trim($result['choices'][0]['message']['content']);
    
    // 紧急情况添加安全提醒
    if ($emergency) {
        $aiReply .= "\n\n⚠️ 紧急提醒：如情况危急，请立即联系现场技术人员。";
    }
    
    // 返回成功响应（包含诊断信息）
    echo json_encode([
        'code' => 200,
        'msg' => 'success',
        'data' => [
            'reply' => $aiReply,
            'emergency' => $emergency,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_time' => $requestTime . 'ms',
            'model_used' => $aiParams['model']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    
    // 简化用户错误信息，但保留技术细节用于调试
    $userMsg = $errorMsg;
    if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, '超时') !== false) {
        $userMsg = 'AI服务响应超时，请稍后重试';
    } elseif (strpos($errorMsg, 'API Key') !== false) {
        $userMsg = 'AI服务认证失败，请联系管理员';
    } elseif (strpos($errorMsg, 'DNS') !== false || strpos($errorMsg, '无法连接') !== false) {
        $userMsg = '网络连接异常，请检查网络状态';
    } elseif (strpos($errorMsg, 'SSL') !== false) {
        $userMsg = 'SSL连接错误，请联系技术支持';
    }
    
    echo json_encode([
        'code' => 500,
        'msg' => $userMsg,
        'debug_info' => $errorMsg, // 调试信息
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
