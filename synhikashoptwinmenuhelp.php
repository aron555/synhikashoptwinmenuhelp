<?php
/**
 * @package    synhikashoptwinmenuhelp
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://delo-design.ru
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

/**
 * Synhikashoptwinmenuhelp plugin.
 *
 * @package   synhikashoptwinmenuhelp
 * @since     1.0.0
 */
class plgSystemSynhikashoptwinmenuhelp extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;


	/**
	 * onAfterRoute.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterInitialise()
	{
		$this->overwriteMenuItemTwimenu();
		$this->checkProductAlias();
	}


	protected function overwriteMenuItemTwimenu()
	{

		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$app = Factory::getApplication();
			$input = $app->input;
			$admin = $app->isClient('administrator');
			$option = $input->getCmd('option');
			$view = $input->getCmd('view');
			$layout = $input->getCmd('layout');
			$data = $input->getArray();

			//проверяем это форма ли меню
			if (
				$admin &&
				$option === 'com_menus' &&
				$view === 'item' &&
				$layout === 'edit' &&
				isset($data['jform'], $data['jform']['id']) &&
				!empty($data['jform']['id'])
			)
			{
				$jform = $data['jform'];

				//подгружаем плагин synhikashopwithmenu
				$query = $this->db->getQuery(true)
					->select($this->db->quoteName(array('params')))
					->from('#__extensions')
					->where( 'element=' . $this->db->quote('synhikashopwithmenu'));
				$extension = $this->db->setQuery( $query )->loadObject();
				$extension->params = new \Joomla\Registry\Registry($extension->params);

				//смотрим на тип меню и сравнимаем с параметром меню из плагина synhikashopwithmenu
				if($extension->params->get('syncmenu', 'nomenu') === $jform['menutype'])
				{
					//выборка пункта меню из базы
					$query = $this->db
						->getQuery(true)
						->select('*')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('id') . ' = ' . (int)$data['jform']['id']);
					$this->db->setQuery($query);
					$currentMenu = $this->db->loadAssoc();

					//поля для сравнения
					$fields = [
						'title',
						'alias',
						'published',
					];

					foreach ($fields as $field)
					{

						if(isset($data['jform'][$field], $currentMenu[$field]))
						{
							//если значения не равны, то помечаем в note
							if($data['jform'][$field] !== $currentMenu[$field])
							{
								$note = explode(',', $data['jform']['note']);

								if(!in_array($field, $note))
								{
									$note[] = $field;
								}

								$note = array_filter($note);
								$data['jform']['note'] = implode(',', $note);
							}
						}
					}

					$input->post->set('jform', $data['jform']);

				}

			}
		}
	}

	/**
	 * @param $element
	 */
	protected function checkProductAlias()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$app = Factory::getApplication();
			$input = $app->input;
			$data = $input->getArray();
			$admin = $app->isClient('administrator');
			$flagChange = false;

			if(
				$admin &&
				$data['option'] === 'com_hikashop' &&
				$data['ctrl'] === 'product' &&
				isset($data['data'], $data['data']['product'])
			)
			{

				if(!(int)$this->params->get('checkalias', 0))
				{
					return;
				}

				$element = (object)$data['data']['product'];
				$dataData = $input->getVar('data');

				//если алиас пустой, то запускаем наполнение его из хикашопа и поставляем в переменную
				//метод addAlias в продукте

				if(empty($dataData['product']['product_alias']))
				{
					include_once rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_hikashop' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'helper.php';
					$configClassHikashop = hikashop_get('class.config');

					//блок кода взят с файла product.php #652 строчка (версия хикашопа на момент написания 4.2.1)
					if($configClassHikashop->get('alias_auto_fill', 1) && !empty($element->product_name))
					{
						$productClassHikashop = hikashop_get('class.product');
						$productClassHikashop->addAlias($element);

						if($configClassHikashop->get('sef_remove_id', 0) && (int)$element->alias > 0)
						{
							$element->alias = $configClassHikashop->get('alias_prefix', 'p') . $element->alias;
						}

						$element->product_alias = $element->alias;
						unset($element->alias);
					}

				}

				//проверяем алиас у продукта
				if(!$this->checkProductAliasOnFree($element))
				{
					$flagChange = true;
					$element->product_alias = $this->getFreeProductAlias($element);
				}

				if($flagChange)
				{
					//перезаписываем входящие данные
					$dataData['product']['product_alias'] = $element->product_alias;
					$input->set('data', $dataData);
				}

			}


		}

	}


	/**
	 *
	 * Проверяем алиас на повторы
	 *
	 * @param $element
	 * @param string $searchAlias
	 * @return bool
	 */
	protected function checkProductAliasOnFree(&$element, $searchAlias = '')
	{
		if(empty($searchAlias))
		{
			$searchAlias = $element->product_alias;
		}

		$db = Factory::getDbo();
		$query = $db
			->getQuery(true)
			->select('product_id')
			->from($db->quoteName('#__hikashop_product'))
			->where($db->quoteName('product_alias') . ' = ' . $db->quote($searchAlias))
			->where($db->quoteName('product_id') . ' != ' . (int)$element->product_id)
			->setLimit(1);
		$db->setQuery($query);
		$search = $db->loadObject();

		if(empty($search->product_id))
		{
			return true;
		}
		else
		{
			return false;
		}

	}


	/**
	 * Получение доступного алиаса для продукта
	 *
	 * @param $element
	 * @return string
	 */
	protected function getFreeProductAlias(&$element)
	{
		$maxI = 1500;
		$i = 1;
		while (true)
		{

			$alias = $element->product_alias . '-' . $i;
			if($this->checkProductAliasOnFree($element, $alias))
			{
				return $alias;
			}
			else
			{
				$i++;
			}


			//сохранность от бесконечного цикла
			if($i > $maxI)
			{
				break;
			}
		}
	}


	/**
	 * Обновление продукта
	 *
	 * @param $id
	 * @param $fieldsSource
	 * @return mixed
	 */
	protected function updateProduct($id, $fieldsSource)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$fields = [];
		foreach ($fieldsSource as $key => $value)
		{
			$fields[] = $db->quoteName($key) . ' = ' . $db->quote($value);
		}
		$conditions = array(
			$db->quoteName('product_id') . ' = ' . (int)$id,
		);
		$query->update($db->quoteName('#__hikashop_product'))->set($fields)->where($conditions);
		$db->setQuery($query);
		return $db->execute();
	}

}