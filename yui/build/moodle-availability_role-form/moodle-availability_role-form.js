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
    var node = Y.Node.create('<span>' + html + '</span>');

    // Fall back to typeid 0 (course role) for old conditions that were saved without a typeid.
    var typeid = (json.typeid !== undefined) ? json.typeid : 0;

    // Set initial value if specified.
    if (json.id !== undefined &&
            node.one('select[name=id] option[value=' + typeid + '_' + json.id + ']')) {
        node.one('select[name=id]').set('value', typeid + '_' + json.id);
    }

    // Add event handlers (first time only).
    if (!M.availability_role.form.addedEvents) {
        M.availability_role.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
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
