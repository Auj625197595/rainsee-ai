# 雨见AI客户端 (RainSee AI Client)

[中文](README.md) | [English](#english)

雨见AI客户端是一个功能强大的 AI 交互平台，旨在提供流畅的多模型 AI 体验。它支持绘画、思维链（思考模式）展示、自定义接口协议，并能通过服务器端的 Docker 环境完美支持 Claude Code。

RainSee AI Client is a powerful AI interaction platform designed to provide a smooth multi-model AI experience. It supports image generation, Chain of Thought (thinking mode) display, custom interface protocols, and fully supports Claude Code via server-side Docker environment.

---

## 🌟 核心功能 / Highlights

- **Claude Code 支持 / Claude Code Support**：支持在服务器环境下通过 Docker 运行 Claude Code，解锁更强大的编程辅助能力。
- **多模式交互 / Multi-mode Interaction**：内置绘画模式与思考过程展示（思维链），让 AI 的创作与思考过程透明可见。
- **高度自定义 / Highly Customizable**：支持自定义 AI 接口协议，轻松接入各种大模型服务。
- **增强辅助 / Enhanced Capabilities**：集成 MarkItDown（文件处理）与 AnyCrawl（网页抓取），为 AI 提供实时信息获取能力。

## 🛠️ 技术栈 / Tech Stack

- **前端 / Frontend**: Vue 2
- **后端 / Backend**: PHP 7.3

---

## 🚀 快速开始 / Quick Start

### 1. 后端部署 (PHP) / Backend Setup

代码位于 `/backend` 目录。

1.  **环境要求 / Requirements**: PHP 7.3
2.  **宝塔面板特别说明 / BT-Panel Note**: 必须在 PHP 管理中删除以下禁用函数：
    - `shell_exec`
    - `proc_open`
3.  **配置文件 / Configuration**:
    修改 `backend/config.php` 中的以下配置：
    - `MARKITDOWN_API_URL`: 对应 Docker 镜像 `ghcr.io/dezoito/markitdown-api`。
    - `WEBSCRAPE_API_URL`: 对应 Docker 镜像 `ghcr.io/any4ai/anycrawl`。
    - `LANG_SEARCH_API_KEY`: 从 [langsearch.com](https://langsearch.com) 获取。
    - `isClaudeToolAllowed`: 实现此函数以进行 Claude Code 的调用鉴权。

#### Docker 依赖服务 / Docker Dependencies

```bash
# Markitdown API
docker run -d --name markitdown-api -p 8490:8490 ghcr.io/dezoito/markitdown-api:latest

# AnyCrawl (Web Scrape)
docker run -d -p 8080:8080 ghcr.io/any4ai/anycrawl:latest
```

### 2. 前端部署 (Vue 2) / Frontend Setup

1.  **安装依赖 / Install Dependencies**:
    ```bash
    npm install
    ```
2.  **开发环境运行 / Development**:
    ```bash
    npm run serve
    ```
3.  **生产环境构建 / Build for Production**:
    ```bash
    npm run build
    ```

3. API 配置 / API Configuration

- **默认后端 / Default Backend**: 项目默认连接到 `icon144.yjllq.com`。
- **自定义后端 / Custom Backend**: 如果您选择自行部署后端，请修改 [config.js](file:///f:/code/aihelp/src/api/config.js#L5) 中的 `API_BASE_DOMAIN` 为您的域名：
  ```javascript
  // f:\code\aihelp\src\api\config.js
  export const API_BASE_DOMAIN = '您的域名.com';
  ```

---

<a name="english"></a>

## 📄 许可证 / License

本项目采用 [MIT License](LICENSE) 开源协议。

This project is licensed under the [MIT License](LICENSE).

---

## GitHub Repository Description (Recommended)

> 一个支持绘画、思考模式、自定义接口及 Docker 运行 Claude Code 的全功能 AI 客户端 (Vue 2 + PHP 7.3)。
> A feature-rich AI client supporting Image Gen, Thinking Mode, Custom APIs, and Claude Code via Docker (Vue 2 + PHP 7.3).
