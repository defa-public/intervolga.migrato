<?namespace Intervolga\Migrato\Data\Module\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Tool\XmlIdProvider\TableXmlIdProvider;

class TypeLang extends BaseData
{
	protected function __construct()
	{
		Loader::includeModule("iblock");
		$this->xmlIdProvider = new TableXmlIdProvider($this);
	}

	public function getFilesSubdir()
	{
		return "/type/";
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$getList = \CIBlockType::GetList();
		while ($type = $getList->fetch())
		{
			foreach ($this->getLanguages() as $lang)
			{
				if ($typeLang = \CIBlockType::GetByIDLang($type["ID"], $lang))
				{
					$id = RecordId::createComplexId(
						array(
							"ID" => strval($typeLang["ID"]),
							"LANG" => strval($lang)
						)
					);
					$record = new Record($this);
					$record->setXmlId($this->getXmlId($id));
					$record->setId($id);
					$record->addFieldsRaw(array(
						"LID" => $typeLang["LID"],
						"NAME" => $typeLang["NAME"],
						"SECTION_NAME" => $typeLang["SECTION_NAME"],
						"ELEMENT_NAME" => $typeLang["ELEMENT_NAME"],
					));
					$record->setDependency(
						"IBLOCK_TYPE_ID",
						new Link(
							Type::getInstance(),
							Type::getInstance()->getXmlId(RecordId::createStringId($typeLang["IBLOCK_TYPE_ID"]))
						)
					);
					$result[] = $record;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array|string[]
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getLanguages()
	{
		$result = array();
		$getList = LanguageTable::getList(array(
			"select" => array(
				"LID"
			)
		));
		while ($language = $getList->fetch())
		{
			$result[] = $language["LID"];
		}

		return $result;
	}

	public function getDependencies()
	{
		return array(
			"IBLOCK_TYPE_ID" => new Link(Type::getInstance()),
		);
	}

	protected function getTypeLanguages($typeId, Record $record) {
		$result = array();
		$fields = $record->getFieldsRaw();
		foreach ($this->getLanguages() as $lang)
		{
			$language = $fields;
			if($fields["LID"] != $lang && $typeLang = \CIBlockType::GetByIDLang($typeId, $lang))
			{
				if(count($result) < 2)
				{
					$generalFields = array_intersect_key($typeLang, array_fill_keys(array('SECTIONS', 'IN_RSS', 'SORT', 'EDIT_FILE_BEFORE','EDIT_FILE_AFTER'), ''));
					$result = array_merge($result, $generalFields);
				}
				$language = $typeLang;
			}
			$result["LANG"][$lang] = array(
				"NAME"          => $language["NAME"],
				"SECTION_NAME"  => $language["SECTION_NAME"],
				"ELEMENT_NAME"  => $language["ELEMENT_NAME"],
			);
		}
		return $result;
	}

	public function update(Record $record)
	{
		if($typeId = $record->getDependency("IBLOCK_TYPE_ID")->getId())
		{
			$arFields = $this->getTypeLanguages($typeId->getValue(), $record);

			$typeObject = new \CIBlockType();
			$isUpdated = $typeObject->Update($typeId->getValue(), $arFields);
			if (!$isUpdated)
			{
				throw new \Exception(trim(strip_tags($typeObject->LAST_ERROR)));
			}
		}
		else
			throw new \Exception("Updating typelang: not found iblocktype for record " . $record->getXmlId());
	}

	public function create(Record $record)
	{
		if($typeId = $record->getDependency("IBLOCK_TYPE_ID")->getId())
		{
			$arFields = $this->getTypeLanguages($typeId->getValue(), $record);

			$typeObject = new \CIBlockType();
			$isUpdated = $typeObject->Update($typeId->getValue(), $arFields);
			if (!$isUpdated)
			{
				throw new \Exception(trim(strip_tags($typeObject->LAST_ERROR)));
			}
			return RecordId::createComplexId(
				array(
					"ID" => strval($typeId->getValue()),
					"LANG" => strval($record->getField("LID"))
				)
			);
		}
		else
			throw new \Exception("Creating typelang: not found iblocktype for record " . $record->getXmlId());
	}
}