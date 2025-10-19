<?php
return [
    'mini_program' => [
        'app_id'  => env('WECHAT_APPID', 'wx5cb19c251351c841'),
        'secret'  => env('WECHAT_SECRET', 'f9b964cb60dd9a7d411121b344397e7a'),
        'default_avatar' => env('WECHAT_DEFAULT_AVATAR', 'https://tc.z.wiki/autoupload/tp9I-EEuTFDi5V8Gsgf1-e1-Mirf2B6lH__jaLH4RQiyl5f0KlZfm6UsKj-HyTuv/20250620/m5pb/1270X1270/image.png/webp'),
        'qrcode_page' => env('WECHAT_QRCODE_PAGE', 'pages/room/room'),
    ],
];
