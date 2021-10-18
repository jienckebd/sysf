# Auto Reference
The purpose of this module is to provide a framework for automating
relationships between Drupal entities based on conditions of the saved entity.

## Installation and configuration

1. Install the module as normal
2. Add an entity reference field to the entity you want to be auto referenced
from the entity being saved.
3. Configure the auto reference to use ief.
4. Use rules to trigger the auto referencing based on conditions.

## Dependencies

Module Name | Reason
--- | ---
inline_entity_form | Provides in place entity edit form.
rules | Provides execution of auto referencing based on conditions.

## autoref entity type
The autoref entity type is used to store the conditions used by plugins.

## Plugin types
The following plugin types are supported by the framework:

Plugin Type | Description | Included Plugins
--- | --- | ---
Matcher | Matches entities based on conditions. | common entity, string match
