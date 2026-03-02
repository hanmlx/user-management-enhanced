<?php
/**
 * 用户管理面板模块
 * 包含用户管理、角色转换等功能
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 用户管理面板功能
add_action('admin_menu', 'create_user_management_page');
function create_user_management_page() {
    add_menu_page(
        __('用户管理', 'user-management-enhanced'),
        __('用户管理', 'user-management-enhanced'),
        'read',
        'user-management',
        'user_management_page_content',
        'dashicons-groups',
        70
    );
}

function user_management_page_content() {
    if (!current_user_can('shop_manager') && !current_user_can('administrator')) {
        wp_die(__('无权访问', 'user-management-enhanced'));
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . __('用户管理面板', 'user-management-enhanced') . '</h1>';
    
    // 分页参数
    $users_per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $users_per_page;
    
    // 处理转换请求
    if (isset($_POST['convert_user'])) {
        $user_id = intval($_POST['user_id']);
        $target_role = sanitize_text_field($_POST['target_role']);
        $user = get_userdata($user_id);
        
        if ($user) {
            // 获取当前角色
            $current_roles = $user->roles;
            
            // 移除所有角色
            foreach ($current_roles as $role) {
                $user->remove_role($role);
            }
            
            // 添加目标角色
            $user->add_role($target_role);
            
            // 如果转换为顾客，自动发送欢迎邮件
            if ($target_role === 'customer') {
                $email_sent = send_woocommerce_new_account_email($user_id);
                
                if ($email_sent) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>' . sprintf(__('用户 %s 已成功转换为 %s，并已自动发送欢迎邮件！', 'user-management-enhanced'), esc_html($user->user_login), esc_html($target_role)) . '</p>';
                    echo '</div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p>' . sprintf(__('用户 %s 已成功转换为 %s，但邮件发送失败！', 'user-management-enhanced'), esc_html($user->user_login), esc_html($target_role)) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('用户 %s 已成功转换为 %s！', 'user-management-enhanced'), esc_html($user->user_login), esc_html($target_role)) . '</p>';
                echo '</div>';
            }
        }
    }
    
    // 处理编辑请求
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if ($user) {
            // 更新用户基本信息
            $user_data = array(
                'ID' => $user_id,
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'user_email' => sanitize_email($_POST['user_email'])
            );
            
            wp_update_user($user_data);
            
            // 更新账单地址
            $billing_fields = array(
                'billing_first_name',
                'billing_last_name',
                'billing_company',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'billing_state',
                'billing_phone',
                'billing_email'
            );
            
            foreach ($billing_fields as $field) {
                if (isset($_POST[$field])) {
                    update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(__('用户 %s 信息已更新！', 'user-management-enhanced'), esc_html($user->user_login)) . '</p>';
            echo '</div>';
        }
    }
    
    // 处理手动发送欢迎邮件请求
    if (isset($_POST['send_welcome_email'])) {
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if ($user) {
            $result = send_woocommerce_new_account_email($user_id);
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('WooCommerce欢迎邮件已成功发送给 %s！', 'user-management-enhanced'), esc_html($user->user_email)) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>' . __('发送邮件时出现错误，请检查WooCommerce邮件设置。', 'user-management-enhanced') . '</p>';
                echo '</div>';
            }
        }
    }
    
    // 获取用户总数
    $all_users = get_users(array(
        'role__in' => array('subscriber', 'customer'),
        'fields' => 'ID'
    ));
    $total_users = count($all_users);
    $total_pages = ceil($total_users / $users_per_page);
    
    // 获取当前页用户
    $users = get_users(array(
        'role__in' => array('subscriber', 'customer'),
        'orderby' => 'registered',
        'order' => 'DESC',
        'offset' => $offset,
        'number' => $users_per_page
    ));
    
    if (empty($users)) {
        echo '<p>' . __('没有找到用户。', 'user-management-enhanced') . '</p>';
    } else {
        // 角色筛选器
        echo '<div class="tablenav top">';
        echo '<div class="alignleft actions">';
        echo '<label for="role-filter">' . __('筛选角色：', 'user-management-enhanced') . '</label>';
        echo '<select name="role_filter" id="role-filter">';
        echo '<option value="all">' . __('所有用户', 'user-management-enhanced') . '</option>';
        echo '<option value="subscriber">' . __('订阅者', 'user-management-enhanced') . '</option>';
        echo '<option value="customer">' . __('顾客', 'user-management-enhanced') . '</option>';
        echo '</select>';
        echo '<button type="button" class="button" id="apply-filter">' . __('应用', 'user-management-enhanced') . '</button>';
        echo '</div>';
        
        // 分页导航
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf(__('%s 个用户', 'user-management-enhanced'), $total_users) . '</span>';
        echo '<span class="pagination-links">';
        
        // 上一页链接
        if ($current_page > 1) {
            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('上一页', 'user-management-enhanced') . '</span><span aria-hidden="true">‹</span></a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
        }
        
        // 页码信息
        echo '<span class="screen-reader-text">' . __('当前页', 'user-management-enhanced') . '</span>';
        echo '<span id="table-paging" class="paging-input">';
        echo '<span class="tablenav-paging-text">' . $current_page . ' / <span class="total-pages">' . $total_pages . '</span></span>';
        echo '</span>';
        
        // 下一页链接
        if ($current_page < $total_pages) {
            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('下一页', 'user-management-enhanced') . '</span><span aria-hidden="true">›</span></a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
        }
        
        echo '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
                <th>' . __('用户名', 'user-management-enhanced') . '</th>
                <th>' . __('公司名字', 'user-management-enhanced') . '</th>
                <th>' . __('邮箱', 'user-management-enhanced') . '</th>
                <th>' . __('角色', 'user-management-enhanced') . '</th>
                <th>' . __('账单地址', 'user-management-enhanced') . '</th>
                <th>KBIS</th>
                <th>' . __('注册时间', 'user-management-enhanced') . '</th>
                <th>' . __('操作', 'user-management-enhanced') . '</th>
            </tr></thead>';
        echo '<tbody>';
        
        foreach ($users as $user) {
            $register_date = date('Y-m-d', strtotime($user->user_registered));
            $user_roles = implode(', ', $user->roles);
            
            // 获取公司名字
            $company_name = get_user_meta($user->ID, 'billing_company', true);
            $company_display = !empty($company_name) ? esc_html($company_name) : __('无公司信息', 'user-management-enhanced');
            
            // 获取账单地址
            $billing_address = get_user_meta($user->ID, 'billing_address_1', true);
            $billing_city = get_user_meta($user->ID, 'billing_city', true);
            $billing_postcode = get_user_meta($user->ID, 'billing_postcode', true);
            $billing_country = get_user_meta($user->ID, 'billing_country', true);
            
            $billing_info = '';
            if ($billing_address) {
                $billing_info = $billing_address;
                if ($billing_city) $billing_info .= ', ' . $billing_city;
                if ($billing_postcode) $billing_info .= ', ' . $billing_postcode;
                if ($billing_country) $billing_info .= ', ' . $billing_country;
            } else {
                $billing_info = __('无账单信息', 'user-management-enhanced');
            }
            
            // 获取KBIS文件
            $kbis = get_field('kbis', 'user_' . $user->ID);
            $kbis_html = __('无KBIS', 'user-management-enhanced');
            
            if ($kbis) {
                $file_name = '';
                
                // 获取文件名
                if (is_numeric($kbis)) {
                    $url = wp_get_attachment_url($kbis);
                    if ($url) {
                        $file_name = basename($url);
                    }
                } elseif (is_array($kbis) && isset($kbis['url'])) {
                    $url = $kbis['url'];
                    $file_name = basename($url);
                } else {
                    $url = $kbis;
                    $file_name = basename($url);
                }
                
                if (!empty($file_name)) {
                    $secure_url = 'https://nexomi.net/?secure_kbis=' . urlencode($file_name);
                    $kbis_html = '<a href="' . esc_url($secure_url) . '" target="_blank">' . __('查看KBIS', 'user-management-enhanced') . '</a>';
                }
            }
            
            echo '<tr class="user-row" data-role="' . esc_attr($user_roles) . '">';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . $company_display . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($user_roles) . '</td>';
            echo '<td>' . esc_html($billing_info) . '</td>';
            echo '<td>' . $kbis_html . '</td>';
            echo '<td>' . esc_html($register_date) . '</td>';
            echo '<td>';
            
            // 转换角色表单
            if (in_array('subscriber', $user->roles)) {
                echo '<form method="post" style="display:inline-block; margin-right:5px;">';
                echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
                echo '<input type="hidden" name="target_role" value="customer">';
                echo '<button type="submit" name="convert_user" class="button button-primary">' . __('转换为顾客', 'user-management-enhanced') . '</button>';
                echo '</form>';
            } else {
                echo '<button type="button" class="button" disabled style="margin-right:5px;">' . __('已是顾客', 'user-management-enhanced') . '</button>';
            }
            
            // 编辑用户按钮
            echo '<button type="button" class="button edit-user-btn" data-user-id="' . esc_attr($user->ID) . '">' . __('编辑用户', 'user-management-enhanced') . '</button>';
            
            // KBIS查看按钮
            echo '<button type="button" class="button kbis-btn" data-user-id="' . esc_attr($user->ID) . '">' . __('查看KBIS', 'user-management-enhanced') . '</button>';
            
            echo '</td>';
            echo '</tr>';
            
            // 编辑用户模态框
            echo '<tr id="edit-user-' . esc_attr($user->ID) . '" class="edit-user-form" style="display:none;">';
            echo '<td colspan="8">';
            echo '<form method="post" class="user-edit-form">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
            
            echo '<table class="form-table">';
            echo '<tr><th><label for="first_name_' . $user->ID . '">' . __('名字', 'user-management-enhanced') . '</label></th>';
            echo '<td><input type="text" name="first_name" id="first_name_' . $user->ID . '" value="' . esc_attr($user->first_name) . '" class="regular-text"></td></tr>';
            
            echo '<tr><th><label for="last_name_' . $user->ID . '">' . __('姓氏', 'user-management-enhanced') . '</label></th>';
            echo '<td><input type="text" name="last_name" id="last_name_' . $user->ID . '" value="' . esc_attr($user->last_name) . '" class="regular-text"></td></tr>';
            
            echo '<tr><th><label for="user_email_' . $user->ID . '">' . __('邮箱', 'user-management-enhanced') . '</label></th>';
            echo '<td><input type="email" name="user_email" id="user_email_' . $user->ID . '" value="' . esc_attr($user->user_email) . '" class="regular-text"></td></tr>';
            
            // 账单地址字段
            echo '<tr><th colspan="2"><h3>' . __('账单地址', 'user-management-enhanced') . '</h3></th></tr>';
            
            $billing_fields = array(
                'billing_first_name' => __('名字', 'user-management-enhanced'),
                'billing_last_name' => __('姓氏', 'user-management-enhanced'),
                'billing_company' => __('公司', 'user-management-enhanced'),
                'billing_address_1' => __('地址行1', 'user-management-enhanced'),
                'billing_address_2' => __('地址行2', 'user-management-enhanced'),
                'billing_city' => __('城市', 'user-management-enhanced'),
                'billing_postcode' => __('邮编', 'user-management-enhanced'),
                'billing_country' => __('国家', 'user-management-enhanced'),
                'billing_state' => __('州/省', 'user-management-enhanced'),
                'billing_phone' => __('电话', 'user-management-enhanced'),
                'billing_email' => __('账单邮箱', 'user-management-enhanced')
            );
            
            foreach ($billing_fields as $meta_key => $label) {
                $value = get_user_meta($user->ID, $meta_key, true);
                echo '<tr><th><label for="' . esc_attr($meta_key) . '_' . $user->ID . '">' . esc_html($label) . '</label></th>';
                echo '<td><input type="text" name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '_' . $user->ID . '" value="' . esc_attr($value) . '" class="regular-text"></td></tr>';
            }
            
            echo '<tr><td colspan="2">';
            echo '<button type="submit" name="update_user" class="button button-primary">' . __('更新信息', 'user-management-enhanced') . '</button>';
            echo '<button type="button" class="button cancel-edit" style="margin-left:5px;">' . __('取消', 'user-management-enhanced') . '</button>';
            
            // 手动发送邮件按钮
            if (in_array('customer', $user->roles)) {
                echo '<button type="submit" name="send_welcome_email" class="button button-secondary" style="margin-left:5px;">' . __('手动发送欢迎邮件', 'user-management-enhanced') . '</button>';
            }
            
            echo '</td></tr>';
            echo '</table>';
            
            echo '</form>';
            echo '</td>';
            echo '</tr>';
            
            // KBIS查看模态框
            echo '<tr id="kbis-user-' . esc_attr($user->ID) . '" class="kbis-form" style="display:none;">';
            echo '<td colspan="8">';
            echo '<div class="kbis-management">';
            echo '<h3>KBIS</h3>';
            
            $kbis = get_field('kbis', 'user_' . $user->ID);
            
            if ($kbis) {
                $file_name = '';
                
                if (is_numeric($kbis)) {
                    $url = wp_get_attachment_url($kbis);
                    if ($url) {
                        $file_name = basename($url);
                    }
                } elseif (is_array($kbis) && isset($kbis['url'])) {
                    $url = $kbis['url'];
                    $file_name = basename($url);
                } else {
                    $url = $kbis;
                    $file_name = basename($url);
                }
                
                if (!empty($file_name)) {
                    $secure_url = 'https://nexomi.net/?secure_kbis=' . urlencode($file_name);
                    echo '<p>KBIS: <a href="' . esc_url($secure_url) . '" target="_blank">' . __('点击查看文件', 'user-management-enhanced') . '</a></p>';
                }
            } else {
                echo '<p>' . __('没有上传KBIS', 'user-management-enhanced') . '</p>';
            }
            
            echo '<button type="button" class="button cancel-kbis" style="margin-top:10px;">' . __('返回', 'user-management-enhanced') . '</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // 底部分页导航
        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf(__('%s 个用户', 'user-management-enhanced'), $total_users) . '</span>';
        echo '<span class="pagination-links">';
        
        // 上一页链接
        if ($current_page > 1) {
            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('第一页', 'user-management-enhanced') . '</span><span aria-hidden="true">«</span></a>';
            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('上一页', 'user-management-enhanced') . '</span><span aria-hidden="true">‹</span></a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
        }
        
        // 页码信息
        echo '<span class="screen-reader-text">' . __('当前页', 'user-management-enhanced') . '</span>';
        echo '<span class="paging-input">';
        echo '<label for="current-page-selector" class="screen-reader-text">' . __('当前页', 'user-management-enhanced') . '</label>';
        echo '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $current_page . '" size="2" aria-describedby="table-paging">';
        echo '<span class="tablenav-paging-text"> ' . __('页，共', 'user-management-enhanced') . ' <span class="total-pages">' . $total_pages . '</span> ' . __('页', 'user-management-enhanced') . '</span>';
        echo '</span>';
        
        // 下一页链接
        if ($current_page < $total_pages) {
            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('下一页', 'user-management-enhanced') . '</span><span aria-hidden="true">›</span></a>';
            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('最后一页', 'user-management-enhanced') . '</span><span aria-hidden="true">»</span></a>';
        } else {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
        }
        
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // 添加JavaScript处理
    echo '<script>
    jQuery(document).ready(function($) {
        // 编辑用户按钮
        $(".edit-user-btn").click(function() {
            var userId = $(this).data("user-id");
            $(".edit-user-form").hide();
            $("#edit-user-" + userId).show();
        });
        
        // 取消编辑按钮
        $(".cancel-edit").click(function() {
            $(this).closest(".edit-user-form").hide();
        });
        
        // KBIS查看按钮
        $(".kbis-btn").click(function() {
            var userId = $(this).data("user-id");
            $(".kbis-form").hide();
            $("#kbis-user-" + userId).show();
        });
        
        // 取消KBIS查看按钮
        $(".cancel-kbis").click(function() {
            $(this).closest(".kbis-form").hide();
        });
        
        // 角色筛选器
        $("#apply-filter").click(function() {
            var role = $("#role-filter").val();
            
            if (role === "all") {
                $(".user-row").show();
            } else {
                $(".user-row").hide();
                $(".user-row[data-role*=\'" + role + "\']").show();
            }
        });
        
        // 当前页码输入框跳转
        $("#current-page-selector").on("keypress", function(e) {
            if (e.which === 13) { // Enter键
                var page = parseInt($(this).val());
                var totalPages = parseInt($(".total-pages").text());
                var currentUrl = window.location.href;
                
                if (page > 0 && page <= totalPages) {
                    // 移除现有的paged参数
                    currentUrl = currentUrl.replace(/([?&])paged=[^&]*(&|$)/, "$1");
                    
                    // 添加新的paged参数
                    var separator = currentUrl.indexOf("?") > -1 ? "&" : "?";
                    window.location.href = currentUrl + separator + "paged=" + page;
                }
            }
        });
    });
    </script>';
    
    // 添加CSS样式
    echo '<style>
    .edit-user-form, .kbis-form {
        background-color: #f9f9f9;
        border-top: 1px solid #ddd;
    }
    .user-edit-form, .kbis-management {
        padding: 15px;
    }
    .tablenav.top, .tablenav.bottom {
        margin-bottom: 15px;
    }
    .tablenav-pages {
        float: right;
    }
    .displaying-num {
        margin-right: 10px;
    }
    .pagination-links {
        display: inline-block;
        vertical-align: middle;
    }
    .current-page {
        width: 40px;
        text-align: center;
    }
    .button-secondary {
        background-color: #f0f0f0;
        border-color: #ccc;
        color: #333;
    }
    .button-secondary:hover {
        background-color: #e0e0e0;
    }
    .button:disabled {
        background-color: #f7f7f7;
        border-color: #ddd;
        color: #a0a5aa;
        cursor: default;
    }
    </style>';
}

// 使用WooCommerce的新账户邮件模板
function send_woocommerce_new_account_email($user_id) {
    if (!class_exists('WC_Emails')) {
        return false;
    }
    
    $wc_emails = WC()->mailer();
    $emails = $wc_emails->get_emails();
    
    foreach ($emails as $email) {
        if ($email->id === 'customer_new_account') {
            $email->trigger($user_id, null, true);
            return true;
        }
    }
    
    return false;
}