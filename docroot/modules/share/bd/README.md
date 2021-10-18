# Better Drupal: Core

Entity Type builder
  - Builds entity types, bundles, and templates of both.
  - Depends on entity builder and config processor.

Entity Builder
  - Builds entities.
  - Depends on config processor.

Config Factory
  - Retrieves config from storage.
  - Expands config derivers.
  - Groups together configs in collections.

Config Processor
  - Processes mappings.
  - Generic logic to process other logic around config.

Entity type templates
  - Defined in .entity.yml.
  - Referenced from entity type resource.template key.

## Design System

Design System components are modeled as component entity type.

The ui_patterns module defines all components. Plugins of patterns are derived
in to component entities.

Patterns are derived from bolt design system components.

Fields of patterns are derived in to fields of component entity type.

## Config

Todo

## Config derivers

Similar to migrate process plugins.

### Piped config derivers

Todo

### Entity type sync

Todo

#### From config schema

Todo

#### From config data

Todo

### Config processor

Todo

### Runtime config

Todo

### Todo

1. Put defaults in schema such as form submit message text defaults like "Your changes have been saved."
