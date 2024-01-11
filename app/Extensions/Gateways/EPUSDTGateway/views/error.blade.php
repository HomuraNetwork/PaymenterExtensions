<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
</head>
<body>
    <div class="container">
        <h1>支付错误</h1>
        @php
            $errorDetails = json_decode($message);
        @endphp

        @if($errorDetails)
            <p>错误信息: {{ $errorDetails->message ?? '未知错误' }}</p>
            <p>状态码: {{ $errorDetails->status_code ?? 'N/A' }}</p>
            <p>请求 ID: {{ $errorDetails->request_id ?? 'N/A' }}</p>
        @else
            <p>{{ $message }}</p>
        @endif
    </div>
</body>
</html>
