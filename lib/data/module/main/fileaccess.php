<? namespace Intervolga\Migrato\Data\Module\Main;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileSystemEntry;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;

class FileAccess extends BaseData
{
	/**
	 * @param \Bitrix\Main\IO\FileSystemEntry $fileSystemEntry
	 * @return bool
	 */
	protected static function isServiceEntry(FileSystemEntry $fileSystemEntry)
	{
		if ($fileSystemEntry->isFile())
		{
			if ($fileSystemEntry->getName() == 'urlrewrite.php')
			{
				return true;
			}
		}
		if ($fileSystemEntry->isDirectory())
		{
			$names = array(
				'bitrix',
				'local',
				'upload',
				'.git',
				'.svn',
			);
			if (in_array($fileSystemEntry->getName(), $names))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \Bitrix\Main\IO\File $file
	 * @return bool
	 */
	protected static function isCodeFile(File $file)
	{
		return ($file->getExtension() == 'php');
	}

	/**
	 * @param Directory $dir
	 * @param bool $accessFilter
	 * @return array
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected static function getFilesRecursive(Directory $dir, $accessFilter = false)
	{
		$result = array();
		if ($dir->isExists())
		{
			foreach ($dir->getChildren() as $fileSystemEntry)
			{
				if ($fileSystemEntry instanceof File)
				{
					if (static::isCodeFile($fileSystemEntry))
					{
						if (!$accessFilter || static::isAccessFile($fileSystemEntry))
						{
							$result[] = $fileSystemEntry;
						}
					}
				}
				if ($fileSystemEntry instanceof Directory)
				{
					$result = array_merge($result, static::getFilesRecursive($fileSystemEntry, $accessFilter));
				}
			}
		}

		return $result;
	}

	/**
	 * @return \Bitrix\Main\IO\File[]
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getAccessFiles()
	{
		$root = Application::getDocumentRoot();
		$dir = new Directory($root);
		/**
		 * @var \Bitrix\Main\IO\File[] $check
		 */
		$check = array();
		foreach ($dir->getChildren() as $fileSystemEntry)
		{
			if (!static::isServiceEntry($fileSystemEntry))
			{
				if ($fileSystemEntry instanceof File)
				{
					if (static::isAccessFile($fileSystemEntry))
					{
						$check[] = $fileSystemEntry;
					}
				}
				if ($fileSystemEntry instanceof Directory)
				{
					$check = array_merge($check, static::getFilesRecursive($fileSystemEntry, true));
				}
			}
		}

		$bitrixAccess = new File($root . '/bitrix/.access.php');
		if ($bitrixAccess->isExists())
		{
			$check[] = $bitrixAccess;
		}

		return $check;
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$files = static::getAccessFiles();
		$root = Application::getDocumentRoot();

		if ($files && is_array($files))
		{
			foreach ($files as $fileObj)
			{
				$PERM = array();

				include $fileObj->getPath();

				$fileAccess = new FileAccess();
				$result = array_merge($result, $fileAccess->getResult(
					$PERM,
					$fileObj->getDirectory()->getPath(),
					$root
				));
			}
		}

		return $result;
	}

	protected function getResult($perm, $fullPath, $root)
	{
		$result = array();

		if ($perm)
		{
			foreach ($perm as $path => $permissions)
			{
				foreach ($permissions as $group => $permission)
				{
					$replaced = str_replace($root, '', $fullPath);
					$dir = $replaced ? : '/';

					$groupIdObject = Group::getInstance()->createId($group);
					$groupXmlId = Group::getInstance()->getXmlId($groupIdObject);

					$result[$dir . $path . $group] = $this->record($dir, $path, $groupXmlId, $permission);
				}
			}
		}

		return $result;
	}

	protected function record($dir, $path, $groupXmlId, $permission)
	{
		if ($dir && $path && $permission)
		{
			$record = new Record($this);
			$complexId = RecordId::createComplexId(array(
				$dir, $path, $groupXmlId,
			));
			$record->setId($this->createId($complexId));
			$record->setXmlId($this->getXmlId($complexId));
			$record->addFieldsRaw(array(
				'DIR' => $dir,
				'PATH' => $path,
				'GROUP' => $groupXmlId,
				'PERMISSION' => $permission,
			));

			return $record;
		}

		return false;
	}

	public function getXmlId($id)
	{
		return md5(serialize($id->getValue()));
	}

	/**
	 * @param \Bitrix\Main\IO\File $file
	 * @return bool
	 */
	protected static function isAccessFile(File $file)
	{
		return ($file->getName() == '.access.php');
	}
}