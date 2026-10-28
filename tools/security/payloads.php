<?php

/**
 * XSS / SSTI payloads of the builder fuzz (tools/security/xss-fuzz.php) and the security tests, from the usual lists
 * (OWASP XSS filter evasion cheat sheet, PortSwigger XSS cheat sheet, PayloadsAllTheThings SSTI): markup, event
 * handlers, attribute breakouts, dangerous URL schemes with obfuscation (case, whitespace, tabs, newlines, control
 * characters, entities), </style> and </script> breakouts, template syntax of both engines.
 * `url` payloads are tried where a URL is expected (they must start with the scheme); 1337 = 7 * 191 is the SSTI probe.
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

return [
    'html' => [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        '<svg onload=alert(1)>',
        '<svg><script>alert(1)</script></svg>',
        '"><script>alert(1)</script>',
        "'><img src=x onerror=alert(1)>",
        '" onmouseover="alert(1)" x="',
        "' onfocus='alert(1)' autofocus x='",
        ' onmouseover=alert(1) ',
        '</style><script>alert(1)</script>',
        '</script><script>alert(1)</script>',
        '</textarea><script>alert(1)</script>',
        '</title><script>alert(1)</script>',
        '<iframe src="javascript:alert(1)"></iframe>',
        '<a href="javascript:alert(1)">x</a>',
        '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>',
        '<!--<script>alert(1)//-->',
        '<details open ontoggle=alert(1)>',
        '{{7*191}}',
        '{=7*191}',
        '{7*191}',
        '${7*191}',
        '{% set a = 1300 + 37 %}{{ a }}',
        "{{ ['id']|map('system')|join }}",
        '{php echo 1337}',
        '*} {=7*191} {*',
        '#} {{7*191}} {#',
        '**}} {=7*191} {**',
        '##}} {{7*191}} {##',
        "x\x00{=7*191}\x01",
        '\\\' . 7*191 . \'',
    ],
    'url' => [
        'javascript:alert(1)',
        'JaVaScRiPt:alert(1)',
        ' javascript:alert(1)',
        "\tjavascript:alert(1)",
        "java\tscript:alert(1)",
        "java\nscript:alert(1)",
        "java\rscript:alert(1)",
        "\x01javascript:alert(1)",
        "javascript\x00:alert(1)",
        'jav&#x09;ascript:alert(1)',
        '&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)',
        'javascript&colon;alert(1)',
        'javascript&#58;alert(1)',
        'vbscript:msgbox(1)',
        'VBScript:msgbox(1)',
        'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
        'data:text/html,<script>alert(1)</script>',
        ' DATA:text/html,<script>alert(1)</script>',
        'data:image/svg+xml,<svg onload=alert(1)>',
        'livescript:alert(1)',
        '//evil.example/x',
        '/\\evil.example/x',
        '\\\\evil.example/x',
        "/\t/evil.example/x",
        ' //evil.example/x',
        '&#47;/evil.example/x',
        'x" onerror="alert(1)',
        'https://ok.example/?q="><script>alert(1)</script>',
        'url(javascript:alert(1))',
        'x.png 1x, javascript:alert(1) 2x',
    ],
];
