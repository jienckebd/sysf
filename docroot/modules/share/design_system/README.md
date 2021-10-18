# Design System

The design system is a total UI/UX solution extending many areas of Drupal core
and contrib tied together with a relatively tiny amount of custom code. The end
goal is to provide a single solution for any Drupal application fully managed
from the Drupal UI, including:

1. 1-off landing page designs
1. Template designs like article detail or teaser displays
1. Global elements like header/footer

## Architecture

The strong majority of logic is handled by core/contrib. The only custom logic
is in this module.

### Contrib and core modules

Module name | Core or contrib | Purpose
--- | --- | ---
layout_builder | Core | Provides drag/drop display building with in place edit.
field_ui | Core | Provides the base "Manage Display", "Manage Form Display", and other routes for each entity type / bundle. View modes / form modes can be overriden per bundle.
field_layout | Core | Extends field_ui to support layouts and regions per entity display.
field_group | Contrib | Provides several group formatters like fieldset, tabs, accordion, etc. to each entity view display and entity form display. layout_builder doesn't support entity form display configuration.
layout_discovery | Core | Provides layout plugin functionality that is required by both layout_builder and field_layout.
efs (Extra Field Suite) | Contrib | Provides the ability to create dynamic extra fields per entity display. Example plugins are WYSIWYG, button, and line.
color_field | Contrib | Provides several entity field widgets for configuring colors, such as the color boxes found on many entity forms.
fontawesome | Contrib | Integrates fontawesome library with an entity field type and ckeditor plugin.
media | Core | Provides re-usable and fieldable media entities like image, video, audio, tweet, etc.
menu_item_extras | Contrib | Extends menu_link_content in core to make menu links fieldable and display configurable.

## Display variants

Layout builder isn't the only display variant. There are 2 options available for
any entity display. The other option is commonly recognized by the "Manage
Display" tab that is not considered in-place editing like layout builder.

### layout_builder

The layout_builder display variant provides in place editing of displays using
a drag/drop builder directly on cloud. Its power removes many of the needs
normally provided by twig. Instead of custom coding twig per display,
layout_builder can be used configure displays in real time in production in a
governed workflow.

Layout builder displays are a sequence of layout_plugin layouts as a row in the
sequence of any number of rows. New rows can be created above or below any row
already in the layout_builder layout.

This display variant is ideal for full screen displays that have their own
unique URL like /node/123.

#### Nested component types

Component types can reference nested component types. This should be used as a
best practice wherever possible.

For example, the "Hero" component type has nested entity reference fields to:

1. Image (entity reference revisions field to image component)
1. CTAs (entity reference revisions field to CTA component)

### field_ui / field_layout

In contrast to layout_builder with any number of layout_plugin layouts, field_ui
displays use a single layout_plugin layout. Fields of the entity can be dragged
and dropped in to different regions of the layout using Drupal tabledrag. This
is different from the in place editing of layout_builder.

This display variant is ideal for small displays that are not full screen like
layout_builder.

### Display modes: view modes and form modes

Display modes can be configured using both layout_builder and field_ui display
variants. Display modes are either view modes or form modes. Each mode can be
uniquely configured using layout_builder and field_ui.

For example, the article content type has at least 2 view modes:

View mode | Purpose
--- | ---
Teaser | Built with field_ui at `Structure > Content Types > Article > Manage Display` by dragging/dropping fields in to region of the layout_plugin layout and configuring field formatters per field."
Default | Built with layout_builder at `Structure > Content Types > Article > Manage Display` and clicking `Manage Layout` to configure the layout_builder display.

So when you're in layout builder, each component type should be listed in
the off canvas dialog (right side dock). There may be multiple of each
component type listed because a block plugin instance is created for each
entity in context, such as the node of the landing page and the current user.
This is confusing but is required to display in layout_builder. So
layout_builder configurations should be restricted to only show the "node"
version of the block plugin.

## Global design system config

The design system has a global configuration form at: `Configuration > User Interface > Design System`

These values configure many areas of entity display configuration in a single
place.

### Utility classes

Wrather than writing CSS and Sass specific to the layout/template you're
building, bootstrap's utility classes are extended to support all the scenarios
required for design.

Reference [w3schools](https://www.w3schools.com/bootstrap4/bootstrap_utilities.asp)
to learn more about the utility classes provided out of the box by bootstrap.
We've added many of our own generic classes to this list.

These classes are plugged in to displays, layouts, field formatters, etc. to
handle any design scenario we need.

Most notable of these utility classes are:

1. .m*-# / m*-*-# to provide margin classes 1 - 10 in size in each direction at each breakpoint.
1. .p*-# / p*-*-# to provide padding classes 1 - 10 in size in each direction at each breakpoint.
1. btn-* to style buttons.

### Color palette

The hook `design_system_field_widget_color_field_widget_box_form_alter` integrates
the colors set on this screen globally with the color boxes widget from
colorfield module.

### Block / layout styles

These are classes styled in: html/themes/custom/alpha/src/scss/01-base/_style.scss

They're then exposed to each layout, region, and block configuration forms.

### Wrapper and text tags

These are the wrapper tags exposed to each layout, region, and block
configuration forms.

### Buttons

These are the button types exposed to each layout, region, and block
configuration forms.

In general, there are 3 types for each of the theme colors.

1. Normal
1. Outline (no background color -- only border color)
1. Transparent (no background or border color)

## Layouts

### Standalone layouts vs template layouts

layout_builder can be used for both standalone layouts and template layouts.

Currently, template layouts are only enabled on:

1. Article content type
1. Product content type

And it's used for standalone templates on:

1. Landing page content type
1. "Panel" block_content

Any entity display can choose to use layout_builder at its "Manage Display"
screen.

## Drupal services and handlers

There are ~5 services and handlers in `html/modules/custom/design_system/src`
that integrate the design system globally.

### \Drupal\design_system\DesignSystem

This class provides generic methods that build configuration forms and process
their values such as:

1. Wrapper element config
1. Heading text element config
1. Button
1. Animate on scroll

It's exposed as Drupal service `design.system` and injected in to both
configuration forms and display processing at runtime.

### \Drupal\design_system\EntityTypeInfo

This class is not a service like `design.system` and is consistent with many
core/contrib modules that need to alter entity types and entity fields.

Specifically:

1. Alters entity type to have canonical/collection routes.
1. Overrides entity_view_display and entity_form_display classes and form handlers to support both layout_builder and field_layout on any entity display.
1. Provides a "Layout" entity operation to entities that support it such as landing page nodes.

All implementations of this class are in: design_system.module

### \Drupal\design_system\EntityDisplay

Provides hook_entity_view_alter() and hook_entity_display_build_alter() logic
to process config around entity field formatters.

All implementations of this class are in: design_system.module

### \Drupal\design_system\FormAlter

Centralizes all custom form alters, including:

1. Entity form alter to implement entity field widget config.
1. Views exposed form alter.

All implementations of this class are in: design_system.module

### \Drupal\design_system\Preprocess

Centralizes all theme preprocessing logic such as:

1. All theme elements.
1. entity
1. entity_form
1. page
1. html
1. form_element
1. input
1. etc.

All implementations of this class are in: design_system.module

### Render elements

Some render elements are overwritten in `\Drupal\design_system\Element` namespace.
Most notable is `\Drupal\design_system\Element\Normalizer` that can alter all
render element definitions and has some common logic applied to many render
elements.

## Example scenarios

### Create new landing page

1. Go to: Content > Add Content > Landing Page
1. Set node title and any other values.
1. Save content.
1. Upon redirect to canonical node URL, Click "Layout" tab.
1. Configure layout as needed.
1. Save in intended workflow state.

### Revise article display / default view mode

1. Go to: Structure > Content Types > Article > Manage Display
1. Click "Manage Layout".
1. Configure layout as needed.
1. Save layout.
1. Layout will immediately apply to all article content in default view mode.

### Revise article display / teaser view mode

1. Go to: Structure > Content Types > Article > Manage Display
1. Click "Teaser" secondary tab.
1. Configure layout and field formatters as needed.
1. Save form.
1. Layout will immediately apply to all article content in teaser view mode.

### Creating or editing an existing component type

1. Go to: Structure > Components > Types
1. Click "Manage Display" for the component type you want to manage.
1. If configuring a view mode other than default, click secondary tab for that view mode such as "Tall" on image component type.
1. Configure component display layout and entity field formatters, just as you would configure article teaser display.
