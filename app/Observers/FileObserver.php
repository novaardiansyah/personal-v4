<?php

namespace App\Observers;

use App\Enums\FileType;
use App\Models\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class FileObserver
{
	public function creating(File $file): void
	{
		if (empty($file->uid)) {
			$file->uid = uuid7();
		}

		if (empty($file->type_id)) {
			$file->type_id = FileType::LocalFile->value;
		}

		if (empty($file->user_id)) {
			$file->user_id = getUser()?->id;
		}

		if (empty($file->file_size) || ((int) $file->file_size < 0)) {
			$file->file_size = 0;
		}

		$file->code = getCode('file');

		if (($file->type_id == FileType::LocalFile->value) && $file->file_path) {
			if (empty($file->file_name)) {
				$file->file_name = pathinfo($file->file_path, PATHINFO_BASENAME);
			}

			if (empty($file->download_url)) {
				$filenameWithoutExtension = pathinfo($file->file_name, PATHINFO_FILENAME);
				$extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
				$expirationCarbon = Carbon::parse($file->scheduled_deletion_time ?? now()->addMonth())->endOfDay();

				$file->download_url = URL::temporarySignedRoute(
					'download',
					$expirationCarbon,
					['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => 'public/attachments']
				);
			}

			foreach (['public', 'local', 'app'] as $disk) {
				if (Storage::disk($disk)->exists($file->file_path)) {
					$file->file_size = Storage::disk($disk)->size($file->file_path);
					break;
				}
			}
		}
	}

	public function updating(File $file): void
	{
		if (empty($file->uid)) {
			$file->uid = uuid7();
		}

		if (($file->type_id == FileType::LocalFile->value) && $file->isDirty('file_path') && $file->file_path) {
			if (empty($file->file_name) || $file->isDirty('file_name')) {
				$file->file_name = pathinfo($file->file_path, PATHINFO_BASENAME);
			}

			$filenameWithoutExtension = pathinfo($file->file_name, PATHINFO_FILENAME);
			$extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
			$expirationCarbon = Carbon::parse($file->scheduled_deletion_time ?? now()->addMonth())->endOfDay();

			$file->download_url = URL::temporarySignedRoute(
				'download',
				$expirationCarbon,
				['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => 'public/attachments']
			);

			foreach (['public', 'local', 'app'] as $disk) {
				if (Storage::disk($disk)->exists($file->file_path)) {
					$file->file_size = Storage::disk($disk)->size($file->file_path);
					break;
				}
			}
		}
	}

	/**
	 * Handle the File "created" event.
	 */
	public function created(File $file): void
	{
		$this->_log('Created', $file);
	}

	/**
	 * Handle the File "updated" event.
	 */
	public function updated(File $file): void
	{
		$this->_log('Updated', $file);
	}

	/**
	 * Handle the File "deleted" event.
	 */
	public function deleted(File $file): void
	{
		$this->_log('Deleted', $file);
	}

	/**
	 * Handle the File "restored" event.
	 */
	public function restored(File $file): void
	{
		$this->_log('Restored', $file);
	}

	/**
	 * Handle the File "force deleted" event.
	 */
	public function forceDeleted(File $file): void
	{
		$file->removeFile();
		$this->_log('Force Deleted', $file);
	}

	private function _log(string $event, File $file): void
	{
		saveActivityLog([
			'event'        => $event,
			'model'        => 'File',
			'subject_type' => File::class,
			'subject_id'   => $file->id,
		], $file);
	}
}
