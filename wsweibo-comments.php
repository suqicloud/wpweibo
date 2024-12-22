<?php
/*
 * 微博心情说说 - 评论功能
*/

function ws_weibo_manage_comments_page() {
    global $wpdb;
    $table_name = $wpdb->prefix. 'ws_weibo_comments';

    // 获取当前评论数量
    $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // 获取通过GET方式传递的用户ID参数
    $user_id_to_query = isset($_GET['user_id'])? intval($_GET['user_id']) : 0;
    // 用于标记是否是按用户ID查询后的结果展示，初始化为false
    $is_query_by_user_id = false; 

    // 删除单条评论
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
        echo "<div class='updated'><p>评论已删除。</p></div>";
    }

    // 删除用户的全部评论
    if (isset($_GET['action']) && $_GET['action'] == 'delete_all' && isset($_GET['user_id'])) {
        $user_id_to_delete_all = intval($_GET['user_id']);
        $wpdb->delete($table_name, ['user_id' => $user_id_to_delete_all]);
        echo "<div class='updated'><p>该用户所有评论已删除。</p></div>";
    }

    // 一键清空全部评论
    if (isset($_GET['action']) && $_GET['action'] == 'delete_all_comments') {
        $wpdb->query("DELETE FROM $table_name");
        echo "<div class='updated'><p>所有评论已全部删除。</p></div>";
    }

    // 获取当前页码
    $paged = isset($_GET['paged'])? intval($_GET['paged']) : 1;
    $posts_per_page = 20; // 每页显示20条评论
    $offset = ($paged - 1) * $posts_per_page;

    // 根据用户ID来构建不同的查询语句
    if ($user_id_to_query > 0) {
        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $user_id_to_query, $posts_per_page, $offset
        ));
        $total_comments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id_to_query));
        $total_pages = ceil($total_comments / $posts_per_page);
        $is_query_by_user_id = true;
    } else {
        // 查询评论数据，限制每页显示的数量
        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $posts_per_page, $offset
        ));

        // 查询评论总数，用于分页计算
        $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_comments / $posts_per_page);
    }
?>
    <div class="wrap">
        <h2>评论管理</h2>
        <p>当前共有 <?php echo esc_html($total_comments);?> 条评论。</p>

        <form method="get">
            <input type="number" name="user_id" placeholder="输入用户ID" value="<?php echo esc_attr($user_id_to_query);?>">
            <input type="submit" value="查询" class="button">
            <?php if ($user_id_to_query > 0) :?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id_to_query);?>">
                <!-- 使用button标签显示中文-->
                <button type="submit" name="action" value="delete_all" class="button" onclick="return confirm('确定要删除该用户的所有评论记录吗？')">删除用户全部评论</button>
            <?php endif;?>
            <!-- 一键清空全部评论按钮 -->
            <button type="submit" name="action" value="delete_all_comments" class="button" onclick="return confirm('确定要删除所有评论吗？此操作不可恢复！')">一键清空所有评论</button>
            <input type="hidden" name="page" value="ws_weibo_comments_manage">
        </form><br>

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th>评论ID</th>
                    <th>对应微博ID</th>
                    <th>用户ID</th>
                    <th>用户IP</th>
                    <th>内容</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment) :?>
                    <tr>
                        <td><?php echo esc_html($comment->id);?></td>
                        <td><?php echo esc_html($comment->feeling_id);?></td>
                        <td><?php echo esc_html($comment->user_id);?></td>
                        <td><?php echo esc_html(get_real_ip_address());?></td>
                        <td><?php echo wp_kses_post($comment->content);?></td>
                        <td><?php echo esc_html($comment->timestamp);?></td>
                        <td>
                            <a href="?page=ws_weibo_comments_manage&action=delete&id=<?php echo esc_attr($comment->id);?>" onclick="return confirm('确定要删除这条评论吗？')">删除</a>
                            <?php if ($user_id_to_query == $comment->user_id) : // 只有当查询的用户ID与当前评论用户ID一致时，显示删除全部按钮?>
                                <input type="submit" name="action" value="delete_all" class="button" onclick="return confirm('确定要删除该用户的所有评论记录吗？')">
                            <?php endif;?>
                        </td>
                    </tr>
                <?php endforeach;?>
            </tbody>
        </table>


        <!-- 分页 -->
        <div class="tablenav bottom">
            <div class="alignleft actions">
                <?php if ($total_pages > 1) :?>
                    <div class="pagination">
                        <?php
                        // 首页按钮
                        if ($paged > 1) {
                            echo '<a href="?page=ws_weibo_comments_manage&paged=1&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">首页</a>';
                        }

                        // 前一页按钮
                        if ($paged > 1) {
                            echo '<a href="?page=ws_weibo_comments_manage&paged='. ($paged - 1). '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">上一页</a>';
                        }

                        // 后一页按钮
                        if ($paged < $total_pages) {
                            echo '<a href="?page=ws_weibo_comments_manage&paged='. ($paged + 1). '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">下一页</a>';
                        }

                        // 最后一页按钮
                        if ($paged < $total_pages) {
                            echo '<a href="?page=ws_weibo_comments_manage&paged='. $total_pages. '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">最后一页</a>';
                        }
                   ?>
                    </div>
                <?php endif;?>
            </div>
        </div>
    </div>
    <?php
}


function ws_weibo_submit_comment() {
    if (isset($_POST['feeling_id']) && isset($_POST['comment_content']) && is_user_logged_in()) {
        global $wpdb;
        $feeling_id = intval($_POST['feeling_id']);
        $comment_content = sanitize_text_field($_POST['comment_content']);
        $user_id = get_current_user_id();

        // 处理评论内容中的屏蔽关键词
        $comment_content = ws_weibo_process_blocked_keywords($comment_content);
        
        // 检查用户是否被禁止发布微博，同时禁止评论
        $banned_users = get_option('ws_weibo_banned_users', []);
        if (in_array($user_id, $banned_users)) {
            wp_send_json_error(['message' => '你已被禁止发布微博，无法评论。']);
            return;
        }
        
        // 插入评论数据
        $table_name = $wpdb->prefix. 'ws_weibo_comments';
        $wpdb->insert($table_name, [
            'feeling_id' => $feeling_id,
            'user_id' => $user_id,
            'content' => $comment_content,
            'timestamp' => current_time('mysql')
        ]);

        // 获取最新评论数据
        $comments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.content, u.display_name AS author, c.timestamp 
                 FROM $table_name c 
                 JOIN {$wpdb->prefix}users u ON c.user_id = u.ID 
                 WHERE c.feeling_id = %d 
                 ORDER BY c.timestamp DESC",
                $feeling_id
            )
        );

        // 返回最新的评论内容
        if ($comments) {
            wp_send_json_success(['comments' => $comments]);
        } else {
            wp_send_json_error(['message' => '没有评论']);
        }
    }
    wp_send_json_error(['message' => '评论失败']);
}
add_action('wp_ajax_ws_weibo_submit_comment', 'ws_weibo_submit_comment');
add_action('wp_ajax_nopriv_ws_weibo_submit_comment', 'ws_weibo_submit_comment');


function ws_weibo_load_comments() {
    if (isset($_POST['feeling_id'])) {
        global $wpdb;
        $feeling_id = intval($_POST['feeling_id']);
        
        // 查询评论
        $table_name = $wpdb->prefix. 'ws_weibo_comments';
        $comments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.content, u.display_name AS author, c.timestamp 
                 FROM $table_name c 
                 JOIN {$wpdb->prefix}users u ON c.user_id = u.ID 
                 WHERE c.feeling_id = %d 
                 ORDER BY c.timestamp DESC",
                $feeling_id
            )
        );

        // 如果有评论，处理屏蔽关键词并返回
        if ($comments) {
            // 对每个评论内容应用关键词屏蔽
            foreach ($comments as &$comment) {
                $comment->content = ws_weibo_process_blocked_keywords($comment->content);
            }
            wp_send_json_success(['comments' => $comments]);
        } else {
            wp_send_json_error(['message' => '没有评论']);
        }
    }
}
add_action('wp_ajax_ws_weibo_load_comments', 'ws_weibo_load_comments');
add_action('wp_ajax_nopriv_ws_weibo_load_comments', 'ws_weibo_load_comments');

function ws_weibo_get_comment_count() {
    if (isset($_POST['feeling_id'])) {
        global $wpdb;
        $feeling_id = intval($_POST['feeling_id']);
        
        // 查询该微博的评论数量
        $comment_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ws_weibo_comments WHERE feeling_id = %d",
                $feeling_id
            )
        );

        if ($comment_count !== null) {
            wp_send_json_success(['comment_count' => $comment_count]);
        } else {
            wp_send_json_error(['message' => '评论数量获取失败']);
        }
    }
}
add_action('wp_ajax_ws_weibo_get_comment_count', 'ws_weibo_get_comment_count');
add_action('wp_ajax_nopriv_ws_weibo_get_comment_count', 'ws_weibo_get_comment_count');
