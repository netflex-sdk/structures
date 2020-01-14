<?php

namespace Netflex\Structure\Traits;

trait SoftDeletes
{
  /**
   * Indicates if the model is currently force deleting.
   *
   * @var bool
   */
  protected $forceDeleting = false;

  /**
   * Perform the actual delete query on this model instance.
   *
   * @return void
   */
  protected function runSoftDelete()
  {
  }

  /**
   * Force a hard delete on a soft deleted model.
   *
   * @return bool|null
   */
  public function forceDelete()
  {
    $this->forceDeleting = true;

    return tap($this->delete(), function ($deleted) {
      $this->forceDeleting = false;

      if ($deleted) {
        $this->fireModelEvent('forceDeleted', false);
      }
    });
  }

  /**
   * @return bool
   */
  public function trashed()
  {
  }

  public static function withTrashed()
  {
  }

  public static function onlyTrashed()
  {
  }

  /**
   * Determine if the model is currently force deleting.
   *
   * @return bool
   */
  public function isForceDeleting()
  {
    return $this->forceDeleting;
  }

  public function restore()
  {
    if ($this->fireModelEvent('restoring') === false) {
      return false;
    }
    //
    $this->fireModelEvent('restored', false);

    return;
  }
}
