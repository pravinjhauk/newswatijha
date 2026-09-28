import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  SelectControl,
  CheckboxControl,
  Button,
  Notice,
} from "@wordpress/components";
import { createElement as h, useEffect, useState } from "@wordpress/element";
import { useSelect, useDispatch, select } from "@wordpress/data";
import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import apiFetch from "@wordpress/api-fetch";

const config = window.sjEditor || { fields: {}, blocks: {} };
function useCatalogue() {
  const [items, setItems] = useState([]);
  useEffect(() => {
    apiFetch({ path: "/swatijha/v1/catalogue" })
      .then(setItems)
      .catch(() => setItems([]));
  }, []);
  return items;
}
const typeMap = {
  credentials: ["sj_clinician"],
  "clinician-profile": ["sj_clinician"],
  contributors: ["sj_clinician"],
  "treatment-options": ["sj_treatment"],
  publications: ["sj_publication"],
  research: ["sj_research"],
  resources: ["sj_resource"],
  reviews: ["sj_review"],
  locations: ["sj_location"],
};
for (const [name, title] of Object.entries(config.blocks)) {
  registerBlockType(`sj/${name}`, {
    apiVersion: 3,
    title,
    category: "widgets",
    icon: "heart",
    attributes: {
      entityIds: { type: "array", default: [] },
      heading: { type: "string", default: "" },
    },
    supports: { html: false },
    edit({ attributes, setAttributes }) {
      const catalogue = useCatalogue();
      const available = catalogue.filter(
        (item) =>
          item.verified &&
          (!typeMap[name] || typeMap[name].includes(item.type)),
      );
      const selectable = ![
        "medical-review",
        "references",
        "practice-contact",
        "enquiry-form",
      ].includes(name);
      return h(
        "div",
        useBlockProps(),
        h(
          InspectorControls,
          {},
          h(
            PanelBody,
            { title: "Content" },
            h(TextControl, {
              label: "Section heading",
              value: attributes.heading,
              onChange: (heading) => setAttributes({ heading }),
            }),
            selectable &&
              available.map((item) =>
                h(CheckboxControl, {
                  key: item.id,
                  label: item.title,
                  checked: attributes.entityIds.includes(item.id),
                  onChange: (checked) =>
                    setAttributes({
                      entityIds: checked
                        ? [...attributes.entityIds, item.id]
                        : attributes.entityIds.filter((id) => id !== item.id),
                    }),
                }),
              ),
          ),
        ),
        h("strong", {}, attributes.heading || title),
        h(
          "p",
          {},
          selectable
            ? attributes.entityIds.length
              ? catalogue
                  .filter((item) => attributes.entityIds.includes(item.id))
                  .map((item) => item.title)
                  .join(" · ")
              : "Select verified records in the block settings."
            : "This component reads the approved page or practice settings.",
        ),
      );
    },
    save() {
      return null;
    },
  });
}
function Field({ field, definition, value, onChange, catalogue }) {
  const [kind, label] = definition;
  if (field === "uuid")
    return value ? h("p", {}, h("small", {}, `Identifier: ${value}`)) : null;
  if (kind === "boolean")
    return h(CheckboxControl, { label, checked: !!value, onChange });
  if (kind.startsWith("enum:"))
    return h(SelectControl, {
      label,
      value: value || "",
      options: [
        { label: "Select…", value: "" },
        ...kind
          .slice(5)
          .split(",")
          .map((v) => ({ label: v, value: v })),
      ],
      onChange,
    });
  if (kind.startsWith("id:"))
    return h(SelectControl, {
      label,
      value: value || 0,
      options: [
        { label: "None", value: 0 },
        ...catalogue
          .filter((item) => kind.slice(3).split(",").includes(item.type))
          .map((item) => ({
            label: `${item.title} (${item.status})`,
            value: item.id,
          })),
      ],
      onChange: (v) => onChange(Number(v)),
    });
  if (kind.startsWith("ids:"))
    return h(
      "fieldset",
      {},
      h("legend", {}, label),
      ...catalogue
        .filter((item) => kind.slice(4).split(",").includes(item.type))
        .map((item) =>
          h(CheckboxControl, {
            key: item.id,
            label: item.title,
            checked: (value || []).includes(item.id),
            onChange: (checked) =>
              onChange(
                checked
                  ? [...(value || []), item.id]
                  : (value || []).filter((id) => id !== item.id),
              ),
          }),
        ),
    );
  if (kind === "urls")
    return h(TextareaControl, {
      label,
      help: "One verified HTTP(S) link per line.",
      value: (value || []).join("\n"),
      onChange: (v) => onChange(v.split("\n").filter(Boolean)),
    });
  if (["credentials", "roles", "references"].includes(kind)) {
    const keys =
      kind === "credentials"
        ? [
            "title",
            "organisation",
            "awarded_on",
            "expires_on",
            "source_url",
            "verified_on",
            "verification",
          ]
        : kind === "roles"
          ? [
              "title",
              "organisation",
              "organisation_url",
              "role_type",
              "started_on",
              "ended_on",
              "source_url",
              "verified_on",
              "verification",
            ]
          : ["entity_id", "anchor", "label"];
    const items = Array.isArray(value) ? value : [];
    return h(
      "div",
      {},
      h("strong", {}, label),
      ...items.map((item, i) =>
        h(
          "div",
          { key: item.uuid || i, className: "sj-editor-repeat" },
          ...keys.map((key) =>
            h(Field, {
              key,
              field: key,
              definition: [
                key === "entity_id"
                  ? "id:sj_reference,sj_publication"
                  : key === "verification"
                    ? "enum:unverified,verified"
                    : key === "role_type"
                      ? "enum:NHS,private,academic,honorary,national"
                      : key.endsWith("_on")
                        ? "date"
                        : "string",
                key.replaceAll("_", " "),
              ],
              value: item[key] || "",
              catalogue,
              onChange: (v) =>
                onChange(
                  items.map((current, j) =>
                    i === j ? { ...current, [key]: v } : current,
                  ),
                ),
            }),
          ),
          h(
            Button,
            {
              variant: "secondary",
              isDestructive: true,
              onClick: () => onChange(items.filter((_, j) => j !== i)),
            },
            "Remove item",
          ),
        ),
      ),
      h(
        Button,
        {
          variant: "secondary",
          onClick: () =>
            onChange([
              ...items,
              kind === "references"
                ? { entity_id: 0, anchor: "", label: "" }
                : { uuid: crypto.randomUUID(), verification: "unverified" },
            ]),
        },
        "Add item",
      ),
    );
  }
  return h(
    field === "summary" || field === "transcript" || field === "excerpt"
      ? TextareaControl
      : TextControl,
    {
      label,
      value: value || "",
      type: kind === "date" ? "date" : kind === "url" ? "url" : "text",
      onChange,
    },
  );
}
function EditorialPanel() {
  const { id, meta, status } = useSelect(
    (select) => ({
      id: select("core/editor").getCurrentPostId(),
      meta: select("core/editor").getEditedPostAttribute("meta") || {},
      status: select("core/editor").getEditedPostAttribute("status"),
    }),
    [],
  );
  const { editPost, savePost } = useDispatch("core/editor");
  const catalogue = useCatalogue();
  const [state, setState] = useState({ state: "draft", locked: false });
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [date, setDate] = useState("");
  const [notes, setNotes] = useState("");
  const [reviewer, setReviewer] = useState(0);
  const refresh = () =>
    apiFetch({ path: `/swatijha/v1/editorial/${id}` })
      .then(setState)
      .catch((error) => setMessage(error.message));
  useEffect(() => {
    if (id) {
      refresh();
      apiFetch({ path: `/swatijha/v1/notes/${id}` })
        .then((result) => setNotes(result.notes || ""))
        .catch((error) => setMessage(error.message));
    }
  }, [id, status]);
  async function act(action) {
    setBusy(true);
    setMessage("");
    try {
      if (action !== "change") {
        await savePost();
        if (select("core/editor").didPostSaveRequestFail())
          throw new Error("Save the draft successfully before proceeding.");
      }
      const result = await apiFetch({
        path: `/swatijha/v1/editorial/${id}/${action}`,
        method: "POST",
        data: { reviewed_on: date, reviewer_id: reviewer },
      });
      if (result.edit_url) window.location.assign(result.edit_url);
      else {
        setMessage(`Review state: ${result.state}`);
        await refresh();
        if (action === "release") window.location.reload();
      }
    } catch (error) {
      setMessage(error.message);
    } finally {
      setBusy(false);
    }
  }
  const fields = Object.entries(config.fields).map(([field, definition]) =>
    h(
      "div",
      { key: field, className: "sj-editor-field" },
      h(Field, {
        field,
        definition,
        value: meta[`_sj_${field}`],
        catalogue,
        onChange: (value) =>
          editPost({ meta: { ...meta, [`_sj_${field}`]: value } }),
      }),
    ),
  );
  const reviewControls = config.canReview
    ? h(
        "div",
        {},
        h(TextControl, {
          label: "Actual medical review date",
          type: "date",
          value: date,
          onChange: setDate,
        }),
        h(SelectControl, {
          label: "Reviewer",
          value: reviewer,
          options: [
            { label: "Select reviewer", value: 0 },
            ...catalogue
              .filter(
                (item) =>
                  item.type === "sj_clinician" &&
                  (item.verified || item.id === id),
              )
              .map((item) => ({ label: item.title, value: item.id })),
          ],
          onChange: (v) => setReviewer(Number(v)),
        }),
        h(
          Button,
          {
            variant: "secondary",
            disabled: busy,
            onClick: () => act("approve"),
          },
          "Approve this saved revision",
        ),
      )
    : null;
  const unlocked = h(
    "div",
    {},
    ...fields,
    h(TextareaControl, {
      label: "Private evidence notes",
      help: "Editorial only. Never include patient information.",
      value: notes,
      onChange: setNotes,
    }),
    h(
      Button,
      {
        variant: "secondary",
        disabled: busy,
        onClick: async () => {
          try {
            await apiFetch({
              path: `/swatijha/v1/notes/${id}`,
              method: "POST",
              data: { notes },
            });
            setMessage("Private notes saved.");
            await refresh();
          } catch (error) {
            setMessage(error.message);
          }
        },
      },
      "Save private notes",
    ),
    h(
      Button,
      { variant: "secondary", disabled: busy, onClick: () => act("request") },
      "Request clinical review",
    ),
    reviewControls,
    config.canPublish &&
      h(
        Button,
        { variant: "primary", disabled: busy, onClick: () => act("release") },
        "Release approved revision",
      ),
  );
  const locked = h(
    "div",
    {},
    h(
      "p",
      {},
      "This approved published version is protected. Create a change draft to edit it.",
    ),
    h(
      Button,
      { variant: "primary", disabled: busy, onClick: () => act("change") },
      "Create clinical change draft",
    ),
  );
  return h(
    PluginDocumentSettingPanel,
    { name: "sj-content", title: "Practice content and clinical review" },
    message &&
      h(Notice, { status: "info", onRemove: () => setMessage("") }, message),
    h("p", { className: "sj-editor-state" }, `Review: ${state.state}`),
    state.locked ? locked : unlocked,
  );
}
registerPlugin("sj-editorial", { render: EditorialPanel });
