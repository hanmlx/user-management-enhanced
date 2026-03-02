<?php
/**
 * KBIS文件管理模块
 * 只有管理员可以访问的KBIS文件管理页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 添加KBIS文件管理菜单
add_action('admin_menu', 'add_kbis_file_manager_menu');
function add_kbis_file_manager_menu() {
    add_submenu_page(
        'user-management',
        __('KBIS文件管理', 'user-management-enhanced'),
        __('KBIS管理', 'user-management-enhanced'),
        'manage_options',
        'kbis-file-manager',
        'display_kbis_file_manager_page'
    );
}

// 显示KBIS文件管理页面
function display_kbis_file_manager_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('您没有权限访问此页面。', 'user-management-enhanced'));
    }
    
    echo '<div class="wrap">';
    echo '<h1>' . __('KBIS文件管理', 'user-management-enhanced') . '</h1>';
    echo '<p>' . __('此页面允许您管理所有用户的KBIS文件。您可以查看、删除或更换KBIS文件。', 'user-management-enhanced') . '</p>';
    
    // 处理删除请求
    if (isset($_POST['delete_kbis'])) {
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if ($user) {
            $kbis = get_field('kbis', 'user_' . $user_id);
            
            if ($kbis) {
                // 如果是附件ID，删除媒体库中的文件
                if (is_numeric($kbis)) {
                    wp_delete_attachment($kbis, true);
                }
                
                // 删除ACF字段
                delete_field('kbis', 'user_' . $user_id);
                
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('用户 %s 的KBIS文件已成功删除。', 'user-management-enhanced'), esc_html($user->user_login)) . '</p>';
                echo '</div>';
            }
        }
    }
    
    // 处理更换请求
    if (isset($_POST['replace_kbis'])) {
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if ($user && !empty($_FILES['new_kbis_file']['name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            $uploadedfile = $_FILES['new_kbis_file'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            
            if ($movefile && !isset($movefile['error'])) {
                // 删除旧文件
                $old_kbis = get_field('kbis', 'user_' . $user_id);
                if ($old_kbis && is_numeric($old_kbis)) {
                    wp_delete_attachment($old_kbis, true);
                }
                
                // 更新为新文件
                update_field('kbis', $movefile['url'], 'user_' . $user_id);
                
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('用户 %s 的KBIS文件已成功更换。', 'user-management-enhanced'), esc_html($user->user_login)) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>' . __('文件上传失败：', 'user-management-enhanced') . $movefile['error'] . '</p>';
                echo '</div>';
            }
        }
    }
    
    // 获取所有有KBIS文件的用户
    $users = get_users(array(
        'meta_query' => array(
            array(
                'key' => 'kbis',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => 'registered',
        'order' => 'DESC'
    ));
    
    if (empty($users)) {
        echo '<div class="notice notice-info">';
        echo '<p>' . __('当前没有找到包含KBIS文件的用户。', 'user-management-enhanced') . '</p>';
        echo '</div>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
                <th>' . __('用户名', 'user-management-enhanced') . '</th>
                <th>' . __('公司名字', 'user-management-enhanced') . '</th>
                <th>' . __('邮箱', 'user-management-enhanced') . '</th>
                <th>KBIS文件</th>
                <th>' . __('文件大小', 'user-management-enhanced') . '</th>
                <th>' . __('上传时间', 'user-management-enhanced') . '</th>
                <th>' . __('操作', 'user-management-enhanced') . '</th>
            </tr></thead>';
        echo '<tbody>';
        
        foreach ($users as $user) {
            $kbis = get_field('kbis', 'user_' . $user->ID);
            
            if (!$kbis) continue;
            
            // 获取文件信息
            $file_name = '';
            $file_size = '-';
            $upload_time = '-';
            
            if (is_numeric($kbis)) {
                $url = wp_get_attachment_url($kbis);
                if ($url) {
                    $file_name = basename($url);
                    $file_path = get_attached_file($kbis);
                    if ($file_path && file_exists($file_path)) {
                        $file_size = size_format(filesize($file_path), 2);
                        $upload_time = get_the_date('Y-m-d H:i', $kbis);
                    }
                }
            } elseif (is_array($kbis) && isset($kbis['url'])) {
                $url = $kbis['url'];
                $file_name = basename($url);
                if (isset($kbis['filesize'])) {
                    $file_size = size_format($kbis['filesize'], 2);
                }
                if (isset($kbis['date'])) {
                    $upload_time = date('Y-m-d H:i', strtotime($kbis['date']));
                }
            } else {
                $url = $kbis;
                $file_name = basename($url);
            }
            
            // 获取公司名字
            $company_name = get_user_meta($user->ID, 'billing_company', true);
            $company_display = !empty($company_name) ? esc_html($company_name) : __('未设置', 'user-management-enhanced');
            
            $kbis_link = '';
            if (!empty($file_name)) {
                $secure_url = 'https://nexomi.net/?secure_kbis=' . urlencode($file_name);
                $kbis_link = '<a href="' . esc_url($secure_url) . '" target="_blank">' . esc_html($file_name) . '</a>';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . $company_display . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . $kbis_link . '</td>';
            echo '<td>' . $file_size . '</td>';
            echo '<td>' . $upload_time . '</td>';
            echo '<td>';
            
            // 删除表单
            echo '<form method="post" style="display:inline-block; margin-right:5px;" onsubmit="return confirm(\'' . __('确定要删除此KBIS文件吗？', 'user-management-enhanced') . '\');">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
            echo '<button type="submit" name="delete_kbis" class="button button-danger">' . __('删除', 'user-management-enhanced') . '</button>';
            echo '</form>';
            
            // 更换表单
            echo '<button type="button" class="button button-primary replace-kbis-toggle" 
                    data-user-id="' . esc_attr($user->ID) . '"
                    data-user-name="' . esc_attr($user->user_login) . '">' . __('更换', 'user-management-enhanced') . '</button>';
            
            // 更换表单模态框
            echo '<div id="replace-form-' . esc_attr($user->ID) . '" style="display:none; margin-top:10px; padding:15px; background:#f9f9f9; border:1px solid #ddd;">';
            echo '<form method="post" enctype="multipart/form-data">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
            echo '<p><strong>' . sprintf(__('为 %s 更换KBIS文件', 'user-management-enhanced'), esc_html($user->user_login)) . '</strong></p>';
            echo '<p>';
            echo '<input type="file" name="new_kbis_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">';
            echo '<span class="description">' . __('支持的文件格式：PDF, JPG, PNG, DOC, DOCX, XLS, XLSX', 'user-management-enhanced') . '</span>';
            echo '</p>';
            echo '<p>';
            echo '<button type="submit" name="replace_kbis" class="button button-primary">' . __('上传并更换', 'user-management-enhanced') . '</button>';
            echo '<button type="button" class="button cancel-replace" data-user-id="' . esc_attr($user->ID) . '">' . __('取消', 'user-management-enhanced') . '</button>';
            echo '</p>';
            echo '</form>';
            echo '</div>';
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '</div>';
    
    // 添加JavaScript
    echo '<script>
    jQuery(document).ready(function($) {
        // 更换按钮点击
        $(".replace-kbis-toggle").click(function() {
            var userId = $(this).data("user-id");
            $("#replace-form-" + userId).slideToggle();
        });
        
        // 取消更换
        $(".cancel-replace").click(function() {
            var userId = $(this).data("user-id");
            $("#replace-form-" + userId).slideUp();
        });
    });
    </script>';
}