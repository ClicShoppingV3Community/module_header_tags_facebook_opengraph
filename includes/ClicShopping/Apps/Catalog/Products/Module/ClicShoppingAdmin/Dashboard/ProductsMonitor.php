<?php
/**
 *
 *  @copyright 2008 - https://www.clicshopping.org
 *  @Brand : ClicShopping(Tm) at Inpi all right Reserved
 *  @Licence GPL 2 & MIT
 *  @licence MIT - Portion of osCommerce 2.4
 *  @Info : https://www.clicshopping.org/forum/trademark/
 *
 */

  namespace ClicShopping\Apps\Catalog\Products\Module\ClicShoppingAdmin\Dashboard;

  use ClicShopping\OM\HTML;
  use ClicShopping\OM\CLICSHOPPING;
  use ClicShopping\OM\Registry;

  use ClicShopping\Apps\Catalog\Products\Products as ProductsApp;

  class ProductsMonitor extends \ClicShopping\OM\Modules\AdminDashboardAbstract {

    protected $lang;
    protected $app;

    protected function init() {

      if (!Registry::exists('Products')) {
        Registry::set('Products', new ProductsApp());
      }

      $this->app = Registry::get('Products');
      $this->lang = Registry::get('Language');

      $this->app->loadDefinitions('Module/ClicShoppingAdmin/Dashboard/products_monitor');

      $this->title = $this->app->getDef('module_admin_dashboard_products_monitor_app_title');
      $this->description = $this->app->getDef('module_admin_dashboard_products_monitor_app_description');

      if ( defined('MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_STATUS') ) {
        $this->sort_order = (int)MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_STATUS == 'True');
      }
    }

    public function getOutput() {
      $CLICSHOPPING_Template = Registry::get('TemplateAdmin');

      $Qproducts = $this->app->db->prepare('select  p.products_id,
                                                    p.products_last_modified,
                                                    p.products_model,
                                                    p.products_tax_class_id,
                                                    p.products_image,
                                                    p.products_price,
                                                    pd.products_name
                                            from :table_products_description pd,
                                                 :table_products p
                                            where (p.products_model = :products_model
                                                  or p.products_model is null
                                                  or p.products_quantity = 0
                                                  or p.products_image is null
                                                  or p.products_image = :products_image
                                                  or p.products_tax_class_id is null
                                                  or p.products_tax_class_id = 0
                                                  or p.products_price is null)
                                            and pd.language_id = :language_id
                                            and p.products_status = 1
                                            and pd.products_id = p.products_id
                                            order by p.products_last_modified desc
                                            limit 6
                                          ');
      $Qproducts->bindValue(':products_model', '');
      $Qproducts->bindValue(':products_image', '');
      $Qproducts->bindInt(':language_id', (int)$this->lang->getId());

      $Qproducts->execute();

      if ($Qproducts->rowCount() == 0) {
        $output = '';
      } else {

        $content_width = 'col-md-' . (int)MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_CONTENT_WIDTH;

        $output = '<span class="' . $content_width . '">';
        $output .= '<div class="separator"></div>';
        $output .= '<div>' . $this->app->getDef('module_admin_dashboard_products_monitor_app_title') . '</div>';
        $output .= '<table class="table table-sm table-hover">';
        $output .= '<thead>';
        $output .= '<tr class="dataTableHeadingRow">';
        $output .= '<th width="50">' . $this->app->getDef('module_admin_dashboard_products_monitor_app_products_id') . '</th>';
        $output .= '<th width="250">' . $this->app->getDef('module_admin_dashboard_products_monitor_app_products_model') . '</th>';
        $output .= '<th width="300">' . $this->app->getDef('module_admin_dashboard_products_monitor_app_products_errors') . '</th>';
        $output .= '<th width="230" class="text-md-center" colspan="2">' . $this->app->getDef('module_admin_dashboard_products_monitor_app_products_last_modified') . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        while ($Qproducts->fetch()) {

          $output .= '  <tr>' .
                     '    <td>' . $Qproducts->valueInt('products_id') . ' </td> ' .
                     '    <td>' . HTML::link(CLICSHOPPING::link(null, 'A&Catalog\Products&Edit&pID=' . $Qproducts->valueInt('products_id')), HTML::outputProtected($Qproducts->value('products_name')));

          $err_list = '';
          $list_no = false;

          if (empty($Qproducts->value('products_model'))) {
            $err_list .= $this->app->getDef('module_admin_dashboard_products_monitor_app_no_model');
            $list_no = true;
          }

          if (empty($Qproducts->value('products_image'))) {
            if ($list_no) {
              $err_list .= ', ';
            }
            $err_list .= $this->app->getDef('module_admin_dashboard_products_monitor_app_no_picture');
            $list_no = true;
          }

          if ($Qproducts->valueInt('products_tax_class_id') == 0) {
            if ($list_no) {
              $err_list .= ', ';
            }
            $err_list .= $this->app->getDef('module_admin_dashboard_products_monitor_app_no_tax');
            $list_no = true;
          }

          if ($Qproducts->value('products_price') == 0) {
            if ($list_no) {
              $err_list .= ', ';
            }
            $err_list .=  $this->app->getDef('module_admin_dashboard_products_monitor_app_no_price');
            $list_no = true;
          }

          if ($Qproducts->value('products_quantity') == 0) {
            if ($list_no) {
              $err_list .= ', ';
            }
            $err_list .= $this->app->getDef('module_admin_dashboard_products_monitor_app_no_stock');
            $list_no = true;
          }

          $output .= '<td>' . $err_list . '</td>';
          $output .= '<td class="text-md-right">' . $Qproducts->value('products_last_modified') . '</td>';
          $output .= '<td class="text-md-right">' . HTML::link(CLICSHOPPING::link(null, 'A&Catalog\Products&Edit&pID=' . $Qproducts->valueInt('products_id')), HTML::image($CLICSHOPPING_Template->getImageDirectory() . 'icons/edit.gif', $this->app->getDef('image_edit'))) . '</td>';
          $output .=  '</tr>';
        } // end while

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</span>';
      }

      return $output;
    }

    public function Install() {

      if ($this->lang->getId() != 2) {

        $this->app->db->save('configuration', [
            'configuration_title' => 'Souhaitez vous activer ce module ?',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_STATUS',
            'configuration_value' => 'True',
            'configuration_description' => 'Souhaitez vous activer ce module ?',
            'configuration_group_id' => '6',
            'sort_order' => '1',
            'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
            'date_added' => 'now()'
          ]
        );

        $this->app->db->save('configuration', [
            'configuration_title' => 'Veuillez selectionner la largeur de l\'affichage?',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_CONTENT_WIDTH',
            'configuration_value' => '12',
            'configuration_description' => 'Veuillez indiquer un nombre compris entre 1 et 12',
            'configuration_group_id' => '6',
            'sort_order' => '1',
            'set_function' => 'clic_cfg_set_content_module_width_pull_down',
            'date_added' => 'now()'
          ]
        );

        $this->app->db->save('configuration', [
            'configuration_title' => 'Ordre de tri d\'affichage',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_SORT_ORDER',
            'configuration_value' => '500',
            'configuration_description' => 'Ordre de tri pour l\'affichage (Le plus petit nombre est montré en premier)',
            'configuration_group_id' => '6',
            'sort_order' => '2',
            'set_function' => '',
            'date_added' => 'now()'
          ]
        );

      } else {

        $this->app->db->save('configuration', [
            'configuration_title' => 'Do you want to enable this Module ?',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_STATUS',
            'configuration_value' => 'True',
            'configuration_description' => 'Do you want to enable this Module ?',
            'configuration_group_id' => '6',
            'sort_order' => '1',
            'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
            'date_added' => 'now()'
          ]
        );

         $this->app->db->save('configuration', [
            'configuration_title' => 'Select the width to display',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_CONTENT_WIDTH',
            'configuration_value' => '12',
            'configuration_description' => 'Select a number between 1 to 12',
            'configuration_group_id' => '6',
            'sort_order' => '1',
            'set_function' => 'clic_cfg_set_content_module_width_pull_down',
            'date_added' => 'now()'
          ]
        );

         $this->app->db->save('configuration', [
            'configuration_title' => 'Sort Order',
            'configuration_key' => 'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_SORT_ORDER',
            'configuration_value' => '500',
            'configuration_description' => 'Sort order of display. Lowest is displayed first.',
            'configuration_group_id' => '6',
            'sort_order' => '2',
            'set_function' => '',
            'date_added' => 'now()'
          ]
        );
      }
    }

    public function keys() {
      return ['MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_STATUS',
              'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_CONTENT_WIDTH',
              'MODULE_ADMIN_DASHBOARD_PRODUCTS_MONITOR_APP_SORT_ORDER'
             ];
    }
  }
