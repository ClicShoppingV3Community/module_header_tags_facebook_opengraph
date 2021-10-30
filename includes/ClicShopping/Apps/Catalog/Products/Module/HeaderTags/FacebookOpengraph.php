<?php
  /**
   *
   * @copyright 2008 - https://www.clicshopping.org
   * @Brand : ClicShopping(Tm) at Inpi all right Reserved
   * @Licence GPL 2 & MIT
   * @licence MIT - Portion of osCommerce 2.4
   * @Info : https://www.clicshopping.org/forum/trademark/
   *
   */

  namespace ClicShopping\Apps\Catalog\Products\Module\HeaderTags;

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\HTML;
  use ClicShopping\OM\CLICSHOPPING;

  use ClicShopping\Apps\Catalog\Products\Products as ProductsApp;

  class FacebookOpengraph extends \ClicShopping\OM\Modules\HeaderTagsAbstract
  {
    protected mixed $lang;
    protected mixed $app;

    protected function init()
    {
      if (!Registry::exists('Products')) {
        Registry::set('Products', new ProductsApp());
      }

      $this->app = Registry::get('Products');
      $this->lang = Registry::get('Language');
      $this->group = 'footer_scripts'; // could be header_tags or footer_scripts

      $this->app->loadDefinitions('Module/HeaderTags/facebook_opengraph');

      $this->title = $this->app->getDef('module_header_tags_product_opengraph_title');
      $this->description = $this->app->getDef('module_header_tags_product_opengraph_description');

      if (\defined('MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_STATUS')) {
        $this->sort_order = (int)MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_STATUS == 'True');
      }
    }

    public function isEnabled()
    {
      return $this->enabled;
    }

    public function getOutput()
    {
      $CLICSHOPPING_Template = Registry::get('Template');
      $CLICSHOPPING_ProductsCommon = Registry::get('ProductsCommon');
      $CLICSHOPPING_Language = Registry::get('Language');
      $CLICSHOPPING_Currencies = Registry::get('Currencies');
      $CLICSHOPPING_Tax = Registry::get('Tax');

      if (!\defined('CLICSHOPPING_APP_CATALOG_PRODUCTS_PD_STATUS') || CLICSHOPPING_APP_CATALOG_PRODUCTS_PD_STATUS == 'False') {
        return false;
      }

      if (isset($_GET['Products'], $_GET['Description'])) {
        $QproductInfo = $this->app->db->prepare('select p.products_id,
                                                         pd.products_name,
                                                         pd.products_description,
                                                         p.products_image,
                                                         p.products_price,
                                                         p.products_quantity,
                                                         p.products_tax_class_id,
                                                         p.products_date_available
                                                  from :table_products p,
                                                       :table_products_description pd,
                                                       :table_products_to_categories p2c,
                                                       :table_categories c
                                                   where p.products_id = :products_id
                                                   and p.products_status = 1
                                                   and p.products_id = pd.products_id
                                                   and pd.language_id = :language_id
                                                   and p.products_id = p2c.products_id
                                                   and p2c.categories_id = c.categories_id
                                                   and c.status = 1
                                                  ');
        $QproductInfo->bindInt(':products_id', $CLICSHOPPING_ProductsCommon->getID());
        $QproductInfo->bindInt(':language_id', (int)$CLICSHOPPING_Language->getId());
        $QproductInfo->execute();

        if ($QproductInfo->rowCount() == 1) {
          $data = [
            'og:type' => 'product',
            'og:title' => $QproductInfo->value('products_name'),
            'og:site_name' => STORE_NAME
          ];

          if (!\is_null(MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_APP_ID)) {
            $data['fb:app_id'] = MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_APP_ID;
          }

          $product_description = substr(trim(preg_replace('/\s\s+/', ' ', strip_tags($QproductInfo->value('products_description')))), 0, 197);

          if (\strlen($product_description) == 197) {
            $product_description .= ' ..';
          }

          $data['og:description'] = $product_description;

          $products_image = $QproductInfo->value('products_image');

          $Qpi = $this->app->db->prepare('select image
                                      from :table_products_images
                                      where products_id = :products_id
                                      order by sort_order
                                      limit 1
                                     ');
          $Qpi->bindInt(':products_id', $CLICSHOPPING_ProductsCommon->getID());
          $Qpi->execute();


          if ($QproductInfo->rowCount() === 1) {
            $products_image = $Qpi->value('image');
          }

          $data['og:image'] = CLICSHOPPING::link($CLICSHOPPING_Template->getDirectoryTemplateImages() . $products_image);

          if ($new_price = $CLICSHOPPING_ProductsCommon->getProductsSpecialPrice()) {
            $products_price = $CLICSHOPPING_Currencies->displayPrice($new_price, $CLICSHOPPING_Tax->getTaxRate($CLICSHOPPING_ProductsCommon->getProductsTaxClassId()));
          } else {
            $products_price = $CLICSHOPPING_Currencies->displayPrice($QproductInfo->value('products_price'), $CLICSHOPPING_Tax->getTaxRate($CLICSHOPPING_ProductsCommon->getProductsTaxClassId()));
          }

          $data['product:price:amount'] = $products_price;
          $data['product:price:currency'] = HTML::sanitize($_SESSION['currency']);
          $data['product:availability'] = ($QproductInfo->value('products_quantity') > 0) ? $this->app->getDef('module_header_tags_product_opengraph_text_in_stock') : $this->app->getDef('module_header_tags_product_opengraph_text_out_of_stock');

          $data['og:url'] = CLICSHOPPING::link('index', 'Products&Description&products_id=' . $QproductInfo->valueInt('products_id'), false);
          $data['og:image:alt'] = $CLICSHOPPING_ProductsCommon->getProductsName($QproductInfo->valueInt('products_id'));

          $data['ia:markup_url'] = CLICSHOPPING::link('index', 'Products&Description&products_id=' . $QproductInfo->valueInt('products_id'), false);
          $data['ia:rules_url'] = CLICSHOPPING::link('index', 'Products&Description&products_id=' . $QproductInfo->valueInt('products_id'), false);
          
          $result = '';

          foreach ($data as $key => $value) {
            $result .= '<meta property="' . HTML::output($key) . '" content="' . HTML::output($value) . '">' . "\n";
          }

          $display_result = $CLICSHOPPING_Template->addBlock($result, $this->group);

          $output =
            <<<EOD
{$display_result}
EOD;

          return $output;
        }
      }
    }

    public function Install()
    {
      $this->app->db->save('configuration', [
          'configuration_title' => 'Souhaitez vous activer ce module ?',
          'configuration_key' => 'MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_STATUS',
          'configuration_value' => 'True',
          'configuration_description' => 'Souhaitez vous activer ce module ?',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );

      $this->app->db->save('configuration', [
          'configuration_title' => 'Insérer le code Facebook App ID',
          'configuration_key' => 'MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_APP_ID',
          'configuration_value' => '',
          'configuration_description' => 'Votre Facebook APP ID<br />Note: Non requis.<br><br><strong>Aide</strong><br /><small>https://developers.facebook.com/docs/opengraph/getting-started/<br />https://developers.facebook.com/docs/opengraph/using-objects/</small>',
          'configuration_group_id' => '6',
          'sort_order' => '2',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );

      $this->app->db->save('configuration', [
          'configuration_title' => 'Ordre de tri d\'affichage',
          'configuration_key' => 'MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_SORT_ORDER',
          'configuration_value' => '180',
          'configuration_description' => 'Ordre de tri pour l\'affichage (Le plus petit nombre est montré en premier)',
          'configuration_group_id' => '6',
          'sort_order' => '185',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );
    }

    public function keys()
    {
      return ['MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_STATUS',
        'MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_APP_ID',
        'MODULE_HEADER_TAGS_PRODUCT_OPENGRAPH_SORT_ORDER'
      ];
    }
  }
