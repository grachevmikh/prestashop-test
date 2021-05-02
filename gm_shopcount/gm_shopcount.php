<?php

/* При заходе на сайт выводить сообщение о том, сколько товаров в магазине находятся в указанном ценовом диапазоне. */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Gm_shopcount extends Module {
	
	public function __construct() {
		
        $this->name = 'gm_shopcount';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Grachev M.A';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Колво товаров');
        $this->description = $this->l('Колво товаров в указанном ценовом диапазоне');
        $this->confirmUninstall = $this->l('Удалить?');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }
	
	public function install() {
        Configuration::updateValue('GM_SHOPCOUNT_MIN_PRICE', 0);
        Configuration::updateValue('GM_SHOPCOUNT_MAX_PRICE', 0);

        return parent::install() && $this->registerHook('hookDisplayFooter');
    }

    public function uninstall() {
        Configuration::deleteByName('GM_SHOPCOUNT_MIN_PRICE');
        Configuration::deleteByName('GM_SHOPCOUNT_MAX_PRICE');

        return parent::uninstall();
    }
	
	public function getContent() {

        if (((bool)Tools::isSubmit('submit')) == true) {
            $this->saveSettings();
        }
		
		$HelperForm = new HelperForm();
		
        $HelperForm->show_toolbar = false;
        $HelperForm->table = $this->table;
        $HelperForm->module = $this;
        $HelperForm->default_form_language = $this->context->language->id;
        $HelperForm->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $HelperForm->identifier = $this->identifier;
        $HelperForm->submit_action = 'submit';
        $HelperForm->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $HelperForm->token = Tools::getAdminTokenLite('AdminModules');
        $HelperForm->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
		
		return $HelperForm->generateForm([[
            'form' => [
                'legend' => [
                'title' => $this->l('Настройки'),
                'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'GM_SHOPCOUNT_MIN_PRICE',
                        'label' => $this->l('Минимальная стоимость'),
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'GM_SHOPCOUNT_MAX_PRICE',
                        'label' => $this->l('Максимальная стоимость'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Сохранить'),
                ],
            ],
        ]]);
    }
	
	protected function getConfigFormValues() {
		return [
				'GM_SHOPCOUNT_MIN_PRICE' => Configuration::get('GM_SHOPCOUNT_MIN_PRICE', null),
				'GM_SHOPCOUNT_MAX_PRICE' => Configuration::get('GM_SHOPCOUNT_MAX_PRICE', null),
			];
	}
	
	protected function saveSettings() {
		
        $formValues = $this->getConfigFormValues();
		
        foreach(array_keys($formValues) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }
	
	public function hookDisplayFooter() {
		$min = Configuration::get('GM_SHOPCOUNT_MIN_PRICE', null);
		$max = Configuration::get('GM_SHOPCOUNT_MAX_PRICE', null);
		if($max === '0') {
			$query = new DbQuery();
			$query->select('price')->from('product')->orderBy('price DESC');
			$max = round(Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query),2);
		}
		$query2 = new DbQuery();
		$query2->select('count(*) as count')->from('product')->where('price >= '.$min.' AND price <= '.$max);
		$count = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query2);
		$this->context->smarty->assign([
          'GM_SHOPCOUNT_MIN_PRICE' => $min,
          'GM_SHOPCOUNT_MAX_PRICE' => $max,
          'GM_SHOPCOUNT_COUNT' => $count
        ]);

        return $this->display(__FILE__, 'info.tpl');
    }
}