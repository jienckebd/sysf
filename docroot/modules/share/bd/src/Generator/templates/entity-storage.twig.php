{% extends "base/class.php.twig" %}

{% block file_path %}
\Drupal\{{ module }}\Entity\Storage\Override\{{ class_name }}.
{% endblock %}

{% block namespace_class %}
namespace Drupal\{{ module }}\Entity\Storage\Override;
{% endblock %}

{% block use_class %}
use Drupal\bd\Entity\Storage\SqlContentEntityStorageTrait;
use {{ class_base }} as Base;
{% endblock %}

/**
 * Extends contrib/core entity storage.
 */
class {{ class_name }} extends Base {

  use SqlContentEntityStorageTrait;

}
