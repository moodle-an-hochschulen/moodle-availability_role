YUI.add('moodle-availability_role-form', function (Y, NAME) {

// JavaScript for form editing role conditions.
// ...@module moodle-availability_role-form.
M.availability_role = M.availability_role || {}; // eslint-disable-line camelcase

// ...@class M.availability_role.form.
// ...@extends M.core_availability.plugin.
M.availability_role.form = Y.Object(M.core_availability.plugin);

// Roles available for selection.
// ...@property roles.
// ...@type Array.
M.availability_role.form.roles = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} roles Array of objects containing roleid => name
 */
M.availability_role.form.initInner = function(roles) {
    this.roles = roles;
};

/**
 * Updates the nonsensical warning visibility for a given form node.
 * A warning is shown when the selected role has been flagged as nonsensical
 * (i.e. the role cannot view this activity type).
 *
 * @method updateNonsensicalWarning
 * @param {Y.Node} node The availability condition form node
 */
M.availability_role.form.updateNonsensicalWarning = function(node) {
    // Get the existing warning node if it exists.
    var warning = node.one('.availability_role_nonsensical_warning');

    // Get the currently selected value from the select element.
    var selected = node.one('select[name=id]').get('value');

    // If no role has been chosen, remove any existing warning and return.
    if (selected === 'choose') {
        if (warning) {
            warning.remove(true);
        }
        return;
    }

    // Split the selected value to extract the typeid and roleid.
    var parts = selected.split('_');
    var typeid = parseInt(parts[0], 10);
    var roleid = parseInt(parts[1], 10);

    // Determine whether the selected role is flagged as nonsensical.
    // Use loose equality (==) to safely handle string/integer differences from PHP JSON serialisation.
    var nonsensical = false;
    Y.each(M.availability_role.form.roles, function(role) {
        if (role.id == roleid && role.typeid == typeid && role.nonsensical) {
            nonsensical = true;
        }
    });

    // If the selected role is nonsensical, ensure the warning is shown. Otherwise, ensure it is removed.
    if (nonsensical) {
        // Create and append the warning div if it does not already exist.
        if (!warning) {
            warning = Y.Node.create(
                '<div class="availability_role_nonsensical_warning alert alert-warning my-2">' +
                M.util.get_string('nonsensical_warning', 'availability_role') +
                '</div>'
            );
            node.append(warning);
        }
    } else {
        // Remove the warning from the DOM if it is currently present.
        if (warning) {
            warning.remove(true);
        }
    }
};

/**
 * Creates and returns the form node for this condition, based on the provided JSON data.
 *
 * @method getNode
 * @param {Object} json JSON data for the condition
 * @return {Y.Node} The form node
 */
M.availability_role.form.getNode = function(json) {
    // Start to create the HTML structure.
    var html = '<label><span class="pe-3">' + M.util.get_string('title', 'availability_role') + '</span> ' +
            '<span class="availability-group">' +
            '<select name="id" class="custom-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';

    // Initialize variable to track when we need to create a new optgroup for a different role type.
    // We start with -1 to ensure the first role type creates an optgroup.
    var curroletypeid = -1;

    // Initialize variable to track whether we have an open optgroup that needs to be closed.
    var optopen = false;

    // Loop through the available roles and create option elements, grouping them by role type.
    Y.each(this.roles, function(role) {
        if (role.typeid != curroletypeid) {
            curroletypeid = role.typeid;
            if (optopen) {
                html += '</optgroup>';
            }
            html += '<optgroup label="' + role.type + '">';
            optopen = true;
        }
        html += '<option value="' + role.typeid + '_' + role.id + '">' + role.name + '</option>';
    });

    // Close any open optgroup.
    if (optopen) {
        html += '</optgroup>';
    }

    // Close the select and label elements.
    html += '</select></span></label>';

    // Create a node from the HTML.
    var node = Y.Node.create('<span class="availability_role_form">' + html + '</span>');

    // Fall back to typeid 0 (course role) for old conditions that were saved without a typeid.
    var typeid = (json.typeid !== undefined) ? json.typeid : 0;

    // In edit mode (json.id is set), pre-select the saved role and update the warning accordingly.
    // For new conditions the select stays at 'choose' and no warning div should appear.
    if (json.id !== undefined &&
            node.one('select[name=id] option[value=' + typeid + '_' + json.id + ']')) {
        node.one('select[name=id]').set('value', typeid + '_' + json.id);
        M.availability_role.form.updateNonsensicalWarning(node);
    }

    // Add event handlers (first time only).
    if (!M.availability_role.form.addedEvents) {
        M.availability_role.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Update the form fields.
            M.core_availability.form.update();

            // Update warning for the changed node.
            // Use the .availability_role_form class to find the correct outer span,
            // as .ancestor('span') would stop at the nested availability-group span.
            var changedNode = Y.one(this).ancestor('.availability_role_form');
            M.availability_role.form.updateNonsensicalWarning(changedNode);
        }, '.availability_role select');
    }

    return node;
};

/**
 * Fills the value object based on the current form fields.
 *
 * @method fillValue
 * @param {Object} value The value object to fill
 * @param {Y.Node} node The form node
 */
M.availability_role.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = 'choose';
    } else {
        selected = selected.split('_');
        value.typeid = parseInt(selected[0], 10);
        value.id = parseInt(selected[1], 10);
    }
};

/**
 * Validates the form and fills the errors array if there are problems.
 *
 * @method fillErrors
 * @param {Array} errors The array to fill with error messages
 * @param {Y.Node} node The form node
 */
M.availability_role.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check grouping item id.
    if (value.id === 'choose') {
        errors.push('availability_role:error_selectrole');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
