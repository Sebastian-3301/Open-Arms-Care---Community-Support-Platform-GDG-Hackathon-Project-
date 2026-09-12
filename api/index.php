<?php
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>false,'error'=>'Apache mod_rewrite is not routing /api requests. Enable mod_rewrite and AllowOverride All.']);
