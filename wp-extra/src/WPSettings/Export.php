<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;

use WPVNTeam\WPSettings\Enqueuer;

class Export extends OptionAbstract
{
    public $view = 'export';

    public function __construct($section, $args = [])
    {
        add_action('admin_init', [$this, 'wpdb_export'], 20);
        parent::__construct($section, $args);
    }

    public function wpdb_export()
    {
        if (! isset($_POST['_wpnonce_export']) || ! wp_verify_nonce($_POST['_wpnonce_export'], 'wp_settings_save_' . $this->section->tab->settings->option_name)) {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            wp_die(__('You need a higher level of permission.'));
        }
        
        if (!empty($_POST[$this->section->tab->settings->option_name])) {
            global $wpdb;
            $host = $wpdb->dbhost;
            $user = $wpdb->dbuser;
            $pass = $wpdb->dbpassword;
            $name = $wpdb->dbname;
            set_time_limit(3000);

            $mysqli = new \mysqli($host,$user,$pass,$name); 
            $mysqli->select_db($name); 
            $mysqli->query("SET NAMES 'utf8'");
            $queryTables = $mysqli->query('SHOW TABLES'); 

            while($row = $queryTables->fetch_row()) { 
                $target_tables[] = $row[0]; 
            }	

            if (isset($tables) && !empty($tables)) { 
                $target_tables = array_intersect($target_tables, $tables); 
            }
        
            $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `".$name."`\r\n--\r\n\r\n\r\n";

            foreach($target_tables as $table){
                if (empty($table)){ continue; }
                $result	= $mysqli->query('SELECT * FROM `'.$table.'`');
                    $fields_amount=$result->field_count;  
                    $rows_num=$mysqli->affected_rows; 	$res = $mysqli->query('SHOW CREATE TABLE '.$table);	$TableMLine=$res->fetch_row(); 
                $content .= "\n\n".$TableMLine[1].";\n\n";   $TableMLine[1]=str_ireplace('CREATE TABLE `','CREATE TABLE IF NOT EXISTS `',$TableMLine[1]);
                for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) {
                    while($row = $result->fetch_row())	{
                        if ($st_counter%100 == 0 || $st_counter == 0 )	{
                            $content .= "\nINSERT INTO ".$table." VALUES";
                        }
                        
                        $content .= "\n(";    for($j=0; $j<$fields_amount; $j++){ 
                            $row[$j] = isset($row[$j]) ? str_replace("\n", "\\n", addslashes((string)$row[$j])) : '';
                            if (isset($row[$j])){
                                $content .= '"'.$row[$j].'"' ;
                            }else{
                                $content .= '""';
                            }

                            if ($j<($fields_amount-1)){
                                $content.= ',';
                            }   
                        }
                        $content .=")";
                        
                        if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
                            $content .= ";";
                        }else{
                            $content .= ",";
                        }
                        $st_counter=$st_counter+1;
                    }
                } 
                $content .="\n\n\n";
            }
            $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
            $backup_name = isset($backup_name) && !empty($backup_name) ? $backup_name : date('Ymd') . '_' . $name . '.sql';
            ob_get_clean();
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"".$backup_name."\"");
            echo $content; exit;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Invalid translation type.') . '</p></div>';
            });
        }
    }
    
    public function render()
    {
        $nonce = 'wp_settings_save_' . $this->section->tab->settings->option_name;
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>">
            <th scope="row" class="titledesc">
                <label for="<?php echo $this->get_id_attribute(); ?>" class="<?php echo $this->get_label_class_attribute(); ?>"><?php echo $this->get_label(); ?></label>
            </th>
            <td class="forminp forminp-text">
                <input type="hidden" name="_wpnonce_export" value="<?php echo wp_create_nonce($nonce); ?>" />
                <input
                    name="<?php echo esc_attr($this->get_name_attribute()); ?>"
                    id="<?php echo $this->get_id_attribute(); ?>"
                    type="submit"
                    value="<?php _e( 'Download Database' ); ?>"
                    class="button components-button is-primary is-compact" 
                    onclick="return confirmExport();">
                <script type="text/javascript">
                function confirmExport() {
                    return confirm("<?php _e( 'Are you sure you want to do this?' ); ?>");
                }
                </script>
                <?php if($description = $this->get_arg('description')) { ?>
                    <p class="description"><?php echo $description; ?></p>
                <?php } ?>

                <?php if($error = $this->has_error()) { ?>
                    <div class="wps-error-feedback"><?php echo $error; ?></div>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
}
