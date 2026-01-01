/**
 * EXIFize My Dates - Block Editor Integration
 *
 * Adds a panel in the post sidebar for managing post dates via EXIF data.
 *
 * @package Exifize_My_Dates
 * @since   2.0.0
 */

(function (wp) {
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editor || wp.editPost;
  const { TextControl, Button, Notice, DateTimePicker, Dropdown } =
    wp.components;
  const { useSelect, useDispatch } = wp.data;
  const { useState, useEffect } = wp.element;
  const { __ } = wp.i18n;

  /**
   * EXIFize Date Panel Component
   */
  const ExifizeDatePanel = function () {
    const [notice, setNotice] = useState(null);
    const [isApplying, setIsApplying] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [metaValue, setMetaValue] = useState("");

    // Get post ID.
    const postId = useSelect(function (select) {
      return select("core/editor").getCurrentPostId();
    }, []);

    // Load initial meta value from server.
    useEffect(
      function () {
        if (postId) {
          loadMetaValue();
        }
      },
      [postId]
    );

    /**
     * Load the current meta value from the database.
     */
    const loadMetaValue = function () {
      const formData = new FormData();
      formData.append("action", "exifize_get_meta");
      formData.append("nonce", window.exifizeEditor.nonce);
      formData.append("post_id", postId);

      fetch(window.exifizeEditor.ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (result) {
          if (result.success) {
            setMetaValue(result.data.meta_value || "");
          }
        })
        .catch(function () {
          // Silently fail on load.
        });
    };

    /**
     * Make an AJAX call.
     */
    const ajaxCall = function (action, extraData) {
      const formData = new FormData();
      formData.append("action", action);
      formData.append("nonce", window.exifizeEditor.nonce);
      formData.append("post_id", postId);

      if (extraData) {
        Object.keys(extraData).forEach(function (key) {
          formData.append(key, extraData[key]);
        });
      }

      return fetch(window.exifizeEditor.ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      }).then(function (response) {
        return response.json();
      });
    };

    // Get the editPost function to update editor state.
    const { editPost } = useDispatch("core/editor");

    /**
     * Apply the date using the full algorithm (same as bulk tool).
     */
    const applyDate = function () {
      if (!window.exifizeEditor) {
        setNotice({
          status: "error",
          message: __("Configuration error.", "exifize-my-dates"),
        });
        return;
      }

      setIsApplying(true);
      setNotice(null);

      ajaxCall("exifize_apply_date")
        .then(function (result) {
          setIsApplying(false);
          if (result.success) {
            // Update the editor's post date to match.
            // Convert "YYYY-MM-DD HH:MM:SS" to ISO format for Gutenberg.
            if (result.data.date) {
              const isoDate = result.data.date.replace(" ", "T");
              editPost({ date: isoDate });
            }
            setNotice({
              status: "success",
              message: result.data.message,
            });
          } else {
            setNotice({
              status: "error",
              message: result.data.message,
            });
          }
        })
        .catch(function () {
          setIsApplying(false);
          setNotice({
            status: "error",
            message: __("Failed to apply date.", "exifize-my-dates"),
          });
        });
    };

    /**
     * Save meta value to database.
     */
    const saveMeta = function (value) {
      setIsSaving(true);

      ajaxCall("exifize_save_meta", { meta_value: value })
        .then(function (result) {
          setIsSaving(false);
          if (result.success) {
            setNotice({
              status: "info",
              message: result.data.message,
            });
          } else {
            setNotice({
              status: "error",
              message: result.data.message,
            });
          }
        })
        .catch(function () {
          setIsSaving(false);
          setNotice({
            status: "error",
            message: __("Failed to save.", "exifize-my-dates"),
          });
        });
    };

    /**
     * Clear meta from database.
     */
    const clearMeta = function () {
      setIsSaving(true);
      setMetaValue("");

      ajaxCall("exifize_clear_meta")
        .then(function (result) {
          setIsSaving(false);
          if (result.success) {
            setNotice({
              status: "info",
              message: result.data.message,
            });
          } else {
            setNotice({
              status: "error",
              message: result.data.message,
            });
          }
        })
        .catch(function () {
          setIsSaving(false);
          setNotice({
            status: "error",
            message: __("Failed to clear.", "exifize-my-dates"),
          });
        });
    };

    /**
     * Set to skip.
     */
    const setSkip = function () {
      setMetaValue("skip");
      saveMeta("skip");
    };

    /**
     * Handle text input change.
     */
    const handleInputChange = function (value) {
      setMetaValue(value);
    };

    /**
     * Handle text input blur - save on blur if valid.
     */
    const handleInputBlur = function () {
      if (metaValue && metaValue !== "skip" && isValidFormat(metaValue)) {
        saveMeta(metaValue);
      }
    };

    /**
     * Handle date picker change.
     */
    const handlePickerChange = function (newDate) {
      if (!newDate) {
        return;
      }
      const date = new Date(newDate);
      const formatted =
        date.getFullYear() +
        "-" +
        String(date.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(date.getDate()).padStart(2, "0") +
        " " +
        String(date.getHours()).padStart(2, "0") +
        ":" +
        String(date.getMinutes()).padStart(2, "0") +
        ":" +
        String(date.getSeconds()).padStart(2, "0");
      setMetaValue(formatted);
      saveMeta(formatted);
    };

    /**
     * Validate the date format.
     */
    const isValidFormat = function (value) {
      if (!value || value === "skip") {
        return true;
      }
      const regex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
      return regex.test(value);
    };

    /**
     * Convert format to Date object for the picker.
     */
    const getDateForPicker = function () {
      if (!metaValue || metaValue === "skip" || !isValidFormat(metaValue)) {
        return null;
      }
      const isoDate = metaValue.replace(" ", "T");
      return new Date(isoDate);
    };

    const isValid = isValidFormat(metaValue);

    return wp.element.createElement(
      PluginDocumentSettingPanel,
      {
        name: "exifize-date-panel",
        title: __("EXIFize My Dates", "exifize-my-dates"),
        className: "exifize-date-panel",
      },
      [
        // Notice
        notice &&
          wp.element.createElement(
            Notice,
            {
              key: "notice",
              status: notice.status,
              isDismissible: true,
              onRemove: function () {
                setNotice(null);
              },
            },
            notice.message
          ),

        // Main button - Apply date using algorithm
        wp.element.createElement(
          Button,
          {
            key: "apply-btn",
            variant: "primary",
            onClick: applyDate,
            isBusy: isApplying,
            disabled: isApplying || isSaving,
            style: { width: "100%", justifyContent: "center" },
          },
          isApplying
            ? __("Applying…", "exifize-my-dates")
            : __("Set Post Date", "exifize-my-dates")
        ),

        // Description for main button
        wp.element.createElement(
          "p",
          {
            key: "apply-desc",
            className: "description",
            style: {
              marginTop: "8px",
              marginBottom: "16px",
              fontSize: "12px",
              color: "#757575",
            },
          },
          __(
            "Uses override below (if set), or Featured Image EXIF, or first attached image EXIF.",
            "exifize-my-dates"
          )
        ),

        // Divider
        wp.element.createElement("hr", {
          key: "divider",
          style: { margin: "16px 0" },
        }),

        // Override section label
        wp.element.createElement(
          "p",
          {
            key: "override-label",
            style: { fontWeight: "600", marginBottom: "8px" },
          },
          __("Date Override", "exifize-my-dates")
        ),

        // Text input with date picker
        wp.element.createElement(
          "div",
          {
            key: "input-wrapper",
            className: "exifize-date-input-wrapper",
            style: { display: "flex", alignItems: "flex-start", gap: "4px" },
          },
          [
            wp.element.createElement(
              "div",
              { key: "input-field", style: { flex: 1 } },
              wp.element.createElement(TextControl, {
                help: isValid
                  ? __(
                      'Format: YYYY-MM-DD HH:MM:SS or "skip"',
                      "exifize-my-dates"
                    )
                  : __(
                      "Invalid format! Use: YYYY-MM-DD HH:MM:SS",
                      "exifize-my-dates"
                    ),
                value: metaValue,
                onChange: handleInputChange,
                onBlur: handleInputBlur,
                placeholder: __("use EXIF", "exifize-my-dates"),
                className: isValid ? "" : "has-error",
              })
            ),
            wp.element.createElement(Dropdown, {
              key: "datepicker",
              popoverProps: { placement: "bottom-end" },
              renderToggle: function ({ isOpen, onToggle }) {
                return wp.element.createElement(Button, {
                  onClick: onToggle,
                  "aria-expanded": isOpen,
                  "aria-label": __("Open date picker", "exifize-my-dates"),
                  icon: "calendar-alt",
                  style: { marginTop: "0" },
                  size: "compact",
                });
              },
              renderContent: function ({ onClose }) {
                return wp.element.createElement(
                  "div",
                  { style: { padding: "8px" } },
                  wp.element.createElement(DateTimePicker, {
                    currentDate: getDateForPicker(),
                    onChange: function (newDate) {
                      handlePickerChange(newDate);
                      onClose();
                    },
                    is12Hour: false,
                  })
                );
              },
            }),
          ]
        ),

        // Skip and Clear buttons
        wp.element.createElement(
          "div",
          {
            key: "buttons",
            className: "exifize-date-buttons",
            style: { display: "flex", gap: "8px", marginTop: "8px" },
          },
          [
            wp.element.createElement(
              Button,
              {
                key: "skip",
                variant: "secondary",
                isSmall: true,
                onClick: setSkip,
                disabled: isSaving || isApplying,
              },
              __("Skip", "exifize-my-dates")
            ),
            wp.element.createElement(
              Button,
              {
                key: "clear",
                variant: "tertiary",
                isSmall: true,
                isDestructive: true,
                onClick: clearMeta,
                disabled: isSaving || isApplying || !metaValue,
              },
              __("Clear", "exifize-my-dates")
            ),
          ]
        ),
      ]
    );
  };

  // Register the plugin.
  registerPlugin("exifize-my-dates", {
    render: ExifizeDatePanel,
    icon: "calendar-alt",
  });
})(window.wp);
