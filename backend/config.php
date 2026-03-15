<?php

define('MARKITDOWN_API_DOMAIN', '127.0.0.1:8490');
define('WEBSCRAPE_API_DOMAIN', '127.0.0.1:8080');

define('MARKITDOWN_API_URL', 'http://' . MARKITDOWN_API_DOMAIN . '/process_file');
//docker run -d --name markitdown-api -p 8490:8490 ghcr.io/dezoito/markitdown-api:latest

define('WEBSCRAPE_API_URL', 'http://' . WEBSCRAPE_API_DOMAIN . '/v1/scrape');
//docker run -d -p 8080:8080 ghcr.io/any4ai/anycrawl:latest

define('LANG_SEARCH_API_KEY', '');
//去https://langsearch.com/login申请搜索api

function isClaudeToolAllowed($apiKey) {
    return true;
}


// Claude Code Docker 运行环境变量配置
define('CLAUDE_API_BASE_URL', '');
define('CLAUDE_API_KEY', '');

