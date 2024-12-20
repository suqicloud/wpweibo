<?php
/*
 * 微博心情说说 - 微博列表
*/

// 微博列表管理页面
function ws_weibo_manage_feelings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix. 'ws_weibo_feelings';

    // 获取当前微博数量
    $total_feelings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // 获取通过GET方式传递的用户ID参数
    $user_id_to_query = isset($_GET['user_id'])? intval($_GET['user_id']) : 0;
    // 用于标记是否是按用户ID查询后的结果展示，初始化为false
    $is_query_by_user_id = false; 

    // 删除单条微博
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
        echo "<div class='updated'><p>微博已删除。</p></div>";
    }

    // 删除用户的全部微博
    if (isset($_GET['action']) && $_GET['action'] == 'delete_all' && isset($_GET['user_id'])) {
        $user_id_to_delete_all = intval($_GET['user_id']);
        $wpdb->delete($table_name, ['user_id' => $user_id_to_delete_all]);
        echo "<div class='updated'><p>该用户所有微博已删除。</p></div>";
    }

    // 删除全部微博
    if (isset($_GET['action']) && $_GET['action'] == 'delete_all_feelings') {
        $wpdb->query("DELETE FROM $table_name");
        echo "<div class='updated'><p>所有微博已全部删除。</p></div>";
    }

    // 获取当前页码
    $paged = isset($_GET['paged'])? intval($_GET['paged']) : 1;
    $posts_per_page = 20; // 每页显示20条微博
    $offset = ($paged - 1) * $posts_per_page;

    // 根据用户ID来构建不同的查询语句
    if ($user_id_to_query > 0) {
        $feelings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $user_id_to_query, $posts_per_page, $offset
        ));
        $total_feelings = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id_to_query));
        $total_pages = ceil($total_feelings / $posts_per_page);
        $is_query_by_user_id = true;
    } else {
        // 查询微博数据，限制每页显示的数量
        $feelings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $posts_per_page, $offset
        ));

        // 查询微博总数，用于分页计算
        $total_feelings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_feelings / $posts_per_page);
    }
 ?>
    <div class="wrap">
        <h2>微博管理</h2>
        <p>当前共有 <?php echo esc_html($total_feelings);?> 条微博。</p>

        <form method="get">
            <input type="number" name="user_id" placeholder="输入用户ID" value="<?php echo esc_attr($user_id_to_query);?>">
            <input type="submit" value="查询" class="button">
            <?php if ($user_id_to_query > 0) :?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id_to_query);?>">
                <!-- 删除用户微博 -->
                <button type="submit" name="action" value="delete_all" class="button" onclick="return confirm('确定要删除该用户的所有微博记录吗？')">删除用户微博</button>
            <?php endif;?>
            <!-- 删除全部微博按钮 -->
            <button type="submit" name="action" value="delete_all_feelings" class="button" onclick="return confirm('确定要删除所有微博吗？此操作不可恢复！')">删除全部微博</button>
            <input type="hidden" name="page" value="ws_weibo_feelings_manage">
        </form><br>

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th>微博ID</th>
                    <th>用户ID</th>
                    <th>用户IP</th>
                    <th>内容</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feelings as $feeling) :?>
                    <tr>
                        <td><?php echo esc_html($feeling->id);?></td>
                        <td><?php echo esc_html($feeling->user_id);?></td>
                        <td><?php echo esc_html(get_real_ip_address());?></td>
                        <td><?php echo wp_kses_post($feeling->content);?></td>
                        <td><?php echo esc_html($feeling->timestamp);?></td>
                        <td>
                            <a href="?page=ws_weibo_feelings_manage&action=delete&id=<?php echo esc_attr($feeling->id);?>" onclick="return confirm('确定要删除这条微博吗？')">删除</a>
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
                            echo '<a href="?page=ws_weibo_feelings_manage&paged=1&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">首页</a>';
                        }

                        // 前一页按钮
                        if ($paged > 1) {
                            echo '<a href="?page=ws_weibo_feelings_manage&paged='. ($paged - 1). '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">上一页</a>';
                        }

                        // 后一页按钮
                        if ($paged < $total_pages) {
                            echo '<a href="?page=ws_weibo_feelings_manage&paged='. ($paged + 1). '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">下一页</a>';
                        }

                        // 最后一页按钮
                        if ($paged < $total_pages) {
                            echo '<a href="?page=ws_weibo_feelings_manage&paged='. $total_pages. '&user_id='. esc_attr($user_id_to_query). ($is_query_by_user_id? '' : '&user_id=0'). '" class="button">最后一页</a>';
                        }
                      ?>
                    </div>
                <?php endif;?>
            </div>
        </div>
    </div>
    <?php
}