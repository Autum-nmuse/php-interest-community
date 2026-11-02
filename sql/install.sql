-- 基于 PHP 的兴趣社区内容管理系统 - 数据库安装脚本
-- 选题：第10类 AI社交与社区应用类
-- 导入方式：phpMyAdmin 导入本文件，或命令行执行 mysql -uroot -p < install.sql
-- 说明：AI 功能为可选加分项，本系统仅在文档中说明，不编码实现，不影响基础评分。

CREATE DATABASE IF NOT EXISTS interest_community DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE interest_community;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `collect`;
DROP TABLE IF EXISTS `like_table`;
DROP TABLE IF EXISTS `comment`;
DROP TABLE IF EXISTS `post`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;

-- 1. 用户表：role 为 user/admin；status 1=正常 0=封禁
CREATE TABLE `user` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL COMMENT 'password_hash 加密后的密码',
    nickname    VARCHAR(50)  NOT NULL DEFAULT '',
    role        VARCHAR(20)  NOT NULL DEFAULT 'user' COMMENT 'user 普通用户 / admin 管理员',
    status      TINYINT      NOT NULL DEFAULT 1 COMMENT '1 正常 0 封禁',
    create_time DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 兴趣分类表
CREATE TABLE `category` (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    cate_name VARCHAR(100) NOT NULL,
    intro     VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 帖子表：status 0=草稿 1=已发布
CREATE TABLE `post` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    uid         INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    content     TEXT NOT NULL,
    cate_id     INT NOT NULL,
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status      TINYINT NOT NULL DEFAULT 1 COMMENT '0 草稿 1 已发布',
    FOREIGN KEY (uid) REFERENCES `user`(id) ON DELETE CASCADE,
    FOREIGN KEY (cate_id) REFERENCES `category`(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. 评论表
CREATE TABLE `comment` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    uid         INT NOT NULL,
    content     VARCHAR(1000) NOT NULL,
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES `post`(id) ON DELETE CASCADE,
    FOREIGN KEY (uid) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. 点赞表：(post_id, uid) 唯一约束，从数据库层防止重复点赞
CREATE TABLE `like_table` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    uid         INT NOT NULL,
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_like_post_user (post_id, uid),
    FOREIGN KEY (post_id) REFERENCES `post`(id) ON DELETE CASCADE,
    FOREIGN KEY (uid) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 收藏表：(post_id, uid) 唯一约束，防止重复收藏
CREATE TABLE `collect` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    uid         INT NOT NULL,
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_collect_post_user (post_id, uid),
    FOREIGN KEY (post_id) REFERENCES `post`(id) ON DELETE CASCADE,
    FOREIGN KEY (uid) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 默认账号（密码均为 123456，password_hash 哈希存储，非明文）
-- 管理员：admin / 123456
-- 普通用户：xiaoming / 123456、xiaohong / 123456
INSERT INTO `user` (id, username, password, nickname, role, status) VALUES
(1, 'admin',    '$2y$12$1xWVkaYkL8azuwJh0J2se.fIaH.YW.xsMovNRRjVF0hJ1Wd.M3QSa', '管理员', 'admin', 1),
(2, 'xiaoming', '$2y$12$1xWVkaYkL8azuwJh0J2se.fIaH.YW.xsMovNRRjVF0hJ1Wd.M3QSa', '小明',   'user',  1),
(3, 'xiaohong', '$2y$12$1xWVkaYkL8azuwJh0J2se.fIaH.YW.xsMovNRRjVF0hJ1Wd.M3QSa', '小红',   'user',  1);

-- 示例分类
INSERT INTO `category` (id, cate_name, intro) VALUES
(1, '编程', '编程学习、技术分享与代码交流'),
(2, '读书', '好书推荐、读书笔记与心得'),
(3, '运动', '跑步、登山、球类等运动打卡'),
(4, '音乐', '歌曲分享、乐器与演出交流'),
(5, '旅行', '旅行攻略、见闻与摄影分享');

-- 示例帖子：6 篇已发布 + 1 篇草稿（草稿仅作者本人可见）
INSERT INTO `post` (id, uid, title, content, cate_id, status) VALUES
(1, 2, 'PHP 新手入门：先搞懂表单提交', '刚开始学 PHP，建议先练熟表单提交：用 form 的 method 区分 GET 和 POST，后端用 $_POST 接收数据，再做非空校验，最后用 PDO 预处理写入数据库。把这一套练熟，增删改查就不难了。大家有什么好用的学习方法，欢迎在评论区分享。', 1, 1),
(2, 3, '《活着》读后感：平凡生活里的韧性', '读完《活着》最大的感受，是福贵在一次次失去之后仍然认真生活。这本书语言很朴素，但力量很强。推荐给最近状态不太好的同学，读完会平静很多。', 2, 1),
(3, 2, '周末登山打卡：驼梁一日行', '周末去爬了驼梁，海拔 2281 米的驼峰视野特别好。建议早上早点出发，带足水和一件外套，山顶风大。运动的快乐很简单，出汗之后整个人都轻松了。', 3, 1),
(4, 3, '分享一份适合自习时听的歌单', '自习时我喜欢听没有歌词的轻音乐和钢琴曲，不容易分心。大家自习或者写代码时都听什么？欢迎补充歌单，互相种草。', 4, 1),
(5, 2, 'MySQL 常用 SQL 语句小结', '把最近复习的 SQL 整理一下：查询 SELECT、插入 INSERT INTO、修改 UPDATE、删除 DELETE，筛选条件用 WHERE，排序用 ORDER BY，限制条数用 LIMIT 做分页。欢迎补充纠正。', 1, 1),
(6, 3, '大理三日游攻略分享', '大理适合慢节奏旅行：第一天逛古城，第二天骑行洱海，第三天去喜洲古镇。注意防晒，住宿建议提前预订。有问题可以在评论区问我。', 5, 1),
(7, 2, '草稿：我的读书计划（未发布）', '这是小明保存的草稿示例：计划本学期读完 6 本书。草稿状态下其他用户看不到这篇帖子，只有作者本人在「我的帖子」里能看到和继续编辑。', 2, 0);

-- 示例评论
INSERT INTO `comment` (post_id, uid, content) VALUES
(1, 3, '总结得很实用，PDO 预处理这块我之前一直没搞明白，现在清楚多了。'),
(1, 1, '管理员路过：欢迎多分享学习心得，注意不要发布违规内容哦。'),
(2, 2, '同感，这本书后劲很大，读完沉默了好久。'),
(3, 3, '照片一定很好看，下次登山求组队！'),
(5, 3, '分页的 LIMIT 用法补充一下：LIMIT 偏移量, 每页条数。');

-- 示例点赞与收藏
INSERT INTO `like_table` (post_id, uid) VALUES (1, 3), (1, 1), (2, 2), (3, 3);
INSERT INTO `collect` (post_id, uid) VALUES (1, 3), (5, 3), (6, 2);
