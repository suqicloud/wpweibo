<?php
/*
 * 微博心情说说 - 用户封禁、违规词、白名单
*/


// 封禁用户设置页面
function ws_weibo_user_settings_page() {
    global $wpdb;

    // 处理表单提交
    if (isset($_POST['submit_user_id'])) {
        $user_id = intval($_POST['user_id']);
        $banned_users = get_option('ws_weibo_banned_users', []);

        // 禁止发布微博
        if (!in_array($user_id, $banned_users)) {
            $banned_users[] = $user_id;
            update_option('ws_weibo_banned_users', $banned_users);
            echo "<div class='updated'><p>用户 $user_id 已被禁止发布微博。</p></div>";
        } else {
            echo "<div class='error'><p>用户 $user_id 已经被禁止发布微博。</p></div>";
        }
    }

    // 解除封禁操作
    if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $banned_users = get_option('ws_weibo_banned_users', []);
        
        // 解除封禁
        if (in_array($user_id, $banned_users)) {
            $banned_users = array_diff($banned_users, [$user_id]);
            update_option('ws_weibo_banned_users', $banned_users);
            echo "<div class='updated'><p>用户 $user_id 的封禁已解除。</p></div>";
        }
    }

    // 获取被封禁用户列表
    $banned_users = get_option('ws_weibo_banned_users', []);

    ?>
    <div class="wrap">
        <h2>禁止用户发布微博</h2>
        <form method="POST" action="">
            <label for="user_id">用户ID：</label>
            <input type="number" name="user_id" id="user_id" required>
            <input type="submit" name="submit_user_id" value="禁止发布" class="button-primary">
        </form><br>
        <p>禁止发布微博，也会同时禁止评论。</p>

        <h3>已被禁止发布微博的用户：</h3>
        <?php if (!empty($banned_users)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>用户ID</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banned_users as $user_id) : ?>
                        <tr>
                            <td><?php echo esc_html($user_id); ?></td>
                            <td><a href="?page=ws_weibo_user_settings&action=remove&user_id=<?php echo esc_attr($user_id); ?>" onclick="return confirm('确定要解除用户 <?php echo esc_attr($user_id); ?> 的封禁吗？')">解除封禁</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>没有被禁止的用户。</p>
        <?php endif; ?>
    </div>
    <?php
}


// 关键词屏蔽管理页面
function ws_weibo_keyword_block_manage_page() {
    if (isset($_POST['submit_keywords'])) {
        $blocked_keywords = sanitize_text_field($_POST['blocked_keywords']);
        update_option('ws_weibo_blocked_keywords', $blocked_keywords);
        echo "<div class='updated'><p>关键词已更新。</p></div>";
    }

    $blocked_keywords = get_option('ws_weibo_blocked_keywords', '');

    ?>
    <div class="wrap">
        <h2>关键词屏蔽管理</h2>
        <form method="POST" action="">
            <label for="blocked_keywords">屏蔽关键词（多个关键词用英文逗号分隔）：</label><br>
            <textarea name="blocked_keywords" id="blocked_keywords" rows="6" class="large-text" style="resize: both;"><?php echo esc_textarea($blocked_keywords); ?></textarea>
            <br>
            <input type="submit" name="submit_keywords" value="保存关键词" class="button-primary">
        </form><br>
        <p>前台出现违规词之后会自动显示为星号*。</p>

        <h3>当前屏蔽的关键词：</h3>
        <p><?php echo esc_html($blocked_keywords); ?></p>
    </div>
    <?php
}

// 处理微博内容中的屏蔽关键词
function ws_weibo_process_blocked_keywords($content) {
    $blocked_keywords = get_option('ws_weibo_blocked_keywords', '');
    $keywords = explode(',', $blocked_keywords);
    $keywords = array_map('trim', $keywords);  // 去除关键词两端的空格

    foreach ($keywords as $keyword) {
        if (!empty($keyword)) {
            $content = str_ireplace($keyword, str_repeat('*', mb_strlen($keyword, 'UTF-8')), $content);
        }
    }

    return $content;
}

// 网址白名单管理页面
function ws_weibo_process_whitelist_update() {
    $current_site_host = parse_url(get_site_url(), PHP_URL_HOST);

    if (isset($_POST['submit_whitelist'])) {
        $whitelist = sanitize_text_field($_POST['whitelist']);
        $whitelist_domains = array_filter(array_map('trim', explode(',', $whitelist)));

        if (!in_array($current_site_host, $whitelist_domains)) {
            $whitelist_domains[] = $current_site_host;
        }
        update_option('ws_weibo_image_whitelist', $whitelist_domains);
        echo "<div class='updated'><p>白名单已更新。</p></div>";
    }

    if (isset($_POST['delete_domain']) && !empty($_POST['domain'])) {
        $domain_to_delete = sanitize_text_field($_POST['domain']);

        if ($domain_to_delete === $current_site_host) {
            echo "<div class='error'><p>当前网站域名不能删除。</p></div>";
        } else {
            $whitelist_domains = get_option('ws_weibo_image_whitelist', []);
            $whitelist_domains = array_filter($whitelist_domains, function($domain) use ($domain_to_delete) {
                return $domain !== $domain_to_delete;
            });
            if (!in_array($current_site_host, $whitelist_domains)) {
                $whitelist_domains[] = $current_site_host;
            }
            update_option('ws_weibo_image_whitelist', array_values($whitelist_domains));
            echo "<div class='updated'><p>域名已删除。</p></div>";
        }
    }
}

// 白名单管理页面展示
function ws_weibo_whitelist_manage_page() {
    ws_weibo_process_whitelist_update();

    $whitelist = get_option('ws_weibo_image_whitelist', []);
    $current_site_host = parse_url(get_site_url(), PHP_URL_HOST);

    $whitelist = array_filter($whitelist, function($domain) use ($current_site_host) {
        return $domain !== $current_site_host;
    });

    ?>
    <div class="wrap">
        <h2>网址白名单管理</h2>
        <form method="POST" action="">
            <label for="whitelist">白名单（多个域名用英文逗号分隔）：</label><br>
            <textarea name="whitelist" id="whitelist" rows="6" class="large-text" style="resize: both;"><?php echo esc_textarea(implode(', ', $whitelist)); ?></textarea>
            <br>
            <input type="submit" name="submit_whitelist" value="保存白名单" class="button-primary">
        </form>
        <br>
        <p>白名单里面的域名，可以直接显示为图片(jpg/png/gif)和视频(mp4/webm)。<br>
        当前站点域名 <strong><?php echo esc_html($current_site_host); ?></strong> 已默认加入白名单。</p>

        <h3>当前白名单域名：</h3>
        <?php if (!empty($whitelist)): ?>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>域名</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($whitelist as $domain): ?>
                        <tr>
                            <td><?php echo esc_html($domain); ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="domain" value="<?php echo esc_attr($domain); ?>">
                                    <input type="submit" name="delete_domain" value="删除" class="button-secondary">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>目前没有白名单域名。</p>
        <?php endif; ?>
    </div>
    <?php
}