# 基于 PHP 的兴趣社区内容管理系统

选题：第 10 类 AI 社交与社区应用类
PHP + MySQL 结课作业项目：普通用户发帖、评论、点赞、收藏，管理员管理帖子、评论、用户与兴趣分类。
以增删改查（CRUD）为核心必做功能；AI 功能仅为可选加分项，本项目只在文档中说明方案、**不编码实现，也不影响基础评分**。

## 功能清单（对照《功能需求文档》）

### 普通用户模块
- 注册、登录、退出登录；被封禁账号无法登录（登录时校验 status，且登录后每次访问受保护页面复查，封禁立即生效）
- 帖子管理：发布新帖（标题、正文、选择兴趣分类）、修改/删除自己发布的帖子、浏览全部帖子、按分类筛选、按关键词搜索标题/正文
- 草稿：发帖可存为草稿（status=0），草稿仅作者本人（和管理员）在「我的帖子」可见，其他用户在列表和详情都看不到
- 评论：对帖子发表评论（分页展示）、删除自己的评论
- 互动：点赞、收藏帖子（再次点击取消），「我的收藏」查看收藏列表

### 管理员模块
- 管理员账号登录（与普通用户同一登录入口，按 role 区分权限）
- 帖子管理：查看所有用户帖子（含草稿），强制删除违规帖子
- 评论管理：查看全部评论，删除违规评论
- 用户管理：查看注册用户列表，封禁/解封违规账号（不能封禁自己和其他管理员）
- 兴趣分类管理：新增、修改、删除分类（如编程、读书、运动）；分类下有帖子时禁止直接删除并给出提示

### 非功能需求落实
- 表单校验：发帖、评论、注册等均做非空校验，标题/正文/评论为空不能提交
- 权限控制：帖子、评论的修改删除在后端按作者 uid 校验，不能操作别人的内容（不是只靠前端隐藏按钮）
- 分页展示：帖子列表每页 5 条，评论每页 5 条，后台帖子/评论/用户列表每页 10 条
- 基础防刷：评论表单带一次性令牌（用后作废，刷新重提会被拦截）+ 提交后按钮禁用；点赞/收藏表设 (post_id, uid) 唯一约束且先查后写，同一用户对同一帖子不能重复点赞/收藏

## 技术栈
- 后端：PHP（原生，无框架、无 Composer）
- 数据库：MySQL（PDO 预处理，防 SQL 注入）
- 前端：原生 HTML / CSS / 少量 JavaScript，无框架、无 CDN，中文响应式界面
- 安全：密码 `password_hash` 加密存储，输出统一 `htmlspecialchars` 转义防 XSS，登录状态用 Session

## 目录结构
```
php-interest-community/
├── config.php            数据库连接、Session、通用函数（登录/权限校验、防重复提交令牌、分页、页头导航）
├── index.php             社区首页：已发布帖子列表 + 分类筛选 + 关键词搜索 + 分页
├── login.php             登录（封禁账号拦截）
├── register.php          用户注册
├── logout.php            退出登录
├── post.php              帖子详情 + 评论（分页）+ 点赞/收藏 + 发表评论
├── post_edit.php         发布 / 编辑帖子（发布或存草稿）
├── post_delete.php       删除帖子（作者本人或管理员，POST 接口）
├── comment_delete.php    删除评论（作者本人或管理员，POST 接口）
├── my_posts.php          我的帖子（含草稿）
├── my_collects.php       我的收藏列表
├── admin_posts.php       帖子管理（管理员）
├── admin_comments.php    评论管理（管理员）
├── admin_users.php       用户管理：封禁 / 解封（管理员）
├── admin_categories.php  兴趣分类管理：增删改查（管理员）
├── assets/
│   ├── style.css         全部样式
│   └── app.js            删除确认、防连点提交
└── sql/
    └── install.sql       建库建表（6 张表）+ 示例数据（导入即用）
```

## 数据库说明（6 张表，与需求文档一致）
| 表 | 说明 |
| --- | --- |
| `user` | 用户：username、password（哈希）、nickname、role（user/admin）、status（1 正常 / 0 封禁）、create_time |
| `category` | 兴趣分类：cate_name、intro |
| `post` | 帖子：uid、title、content、cate_id、create_time、update_time、status（0 草稿 / 1 已发布） |
| `comment` | 评论：post_id、uid、content、create_time |
| `like_table` | 点赞：post_id、uid、create_time，(post_id, uid) 唯一 |
| `collect` | 收藏：post_id、uid、create_time，(post_id, uid) 唯一 |

删帖时其评论、点赞、收藏由外键 `ON DELETE CASCADE` 一并删除；分类被帖子引用时外键 `RESTRICT` 阻止误删。

## 安装运行（phpStudy / XAMPP）
1. 启动 Apache 和 MySQL，把项目文件夹放进网站根目录（如 phpStudy 的 `WWW`、XAMPP 的 `htdocs`）。
2. 打开 phpMyAdmin，导入 `sql/install.sql`（自动建库 `interest_community`、建 6 张表、写入示例数据）。
3. 按本地环境修改 `config.php` 顶部的数据库账号密码（`DB_USER` / `DB_PASS`，phpStudy 默认多为 `root/root`，XAMPP 多为 `root` 无密码）。
4. 浏览器访问 `http://localhost/php-interest-community/index.php` 即可使用。

命令行环境也可用 PHP 内置服务器快速预览（需本地 MySQL 已导入数据）：`php -S localhost:8000`

## 默认账号（密码均为 123456，哈希存储）
- 管理员：`admin / 123456`
- 普通用户：`xiaoming / 123456`、`xiaohong / 123456`（也可以自行注册）

## 示例数据
- 分类 5 个：编程、读书、运动、音乐、旅行
- 帖子 7 篇：6 篇已发布 + 1 篇草稿（小明的草稿，演示草稿仅自己可见）
- 评论 5 条、点赞 4 条、收藏 3 条，导入后即可演示搜索、评论、点赞、收藏与后台管理

## 可选 AI 拓展功能（加分项，仅文档说明，不实现）
按需求文档，AI 能力为选做拓展，本项目不编码实现，以下仅为方案说明，答辩时可口头介绍：
- **AI 内容检测**：帖子/评论发布后调用敏感词或内容审核模型识别违规、涉黄涉暴等内容，标记风险等级并推送管理员优先审核，辅助现有的人工删除流程。
- **AI 兴趣推荐**：根据用户的浏览、点赞、收藏记录统计其偏好分类，在首页优先推荐同分类的热门帖子，提升社区活跃度。
上述功能不接入不影响注册、发帖、评论、点赞、收藏及后台管理等基础功能与评分。
