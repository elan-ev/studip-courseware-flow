<script setup>
import { computed } from 'vue';
import { useGettext } from 'vue3-gettext';
import StudipIcon from '@/components/studip/StudipIcon.vue';

const ACTIONMENU_THRESHOLD = 2;

const { $gettext } = useGettext();

const props = defineProps({
    items: Array,
    collapseAt: {
        default: null,
    },
    context: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['']);

const linkAttributes = (item) => {
    let attributes = item.attributes;
    attributes.class = item.classes;

    if (item.disabled) {
        attributes.disabled = true;
    }

    if (item.url) {
        attributes.href = item.url;
    }

    return attributes;
};

const linkEvents = (item) => {
    let events = {};
    if (item.emit) {
        events.click = (e) => {
            e.preventDefault();
            emit(item.emit, ...item.emitArguments);
            close();
        };
    }
    return events;
};

const close = () => {
    window.STUDIP.ActionMenu.closeAll();
};

const navigationItems = computed(() => {
    return props.items.map((item) => {
        let classes = item.classes ?? '';
        if (item.disabled) {
            classes += ' action-menu-item-disabled';
        }
        return {
            label: item.label,
            url: item.url || '#',
            emit: item.emit || false,
            emitArguments: item.emitArguments || [],
            icon: item.icon
                ? {
                      shape: item.icon,
                      role: item.disabled ? 'inactive' : 'clickable',
                  }
                : false,
            type: item.type || 'link',
            name: item.name ?? null,
            classes: classes.trim(),
            attributes: item.attributes || {},
            disabled: item.disabled,
        };
    });
});

const shouldCollapse = computed(() => {
    const collapseAt = props.collapseAt ?? ACTIONMENU_THRESHOLD + 1;

    if (collapseAt === false) {
        return false;
    }
    if (collapseAt === true) {
        return true;
    }
    return (
        Number.parseInt(collapseAt) <=
        props.items.filter((item) => item.type !== 'separator').length
    );
});
const title = computed(() => {
    return props.context
        ? $gettext('Aktionsmenü für %{context}', {
              context: props.context,
          })
        : $gettext('Aktionsmenü');
});
</script>

<template>
    <div v-if="shouldCollapse" class="action-menu">
        <button class="action-menu-icon" :title="title" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="action-menu-content">
            <div class="action-menu-title">
                {{ $gettext('Aktionen') }}
            </div>
            <ul class="action-menu-list">
                <li v-for="item in navigationItems" :key="item.id" class="action-menu-item">
                    <hr v-if="item.type === 'separator'" />
                    <a
                        v-else-if="item.type === 'link'"
                        v-bind="linkAttributes(item)"
                        v-on="linkEvents(item)"
                    >
                        <StudipIcon
                            v-if="item.icon !== false"
                            :shape="item.icon.shape"
                            :role="item.icon.role"
                        />
                        <span v-else class="action-menu-no-icon"></span>
                        {{ item.label }}
                    </a>
                    <label
                        v-else-if="item.icon"
                        class="undecorated"
                        v-bind="linkAttributes(item)"
                        v-on="linkEvents(item)"
                    >
                        <StudipIcon
                            :shape="item.icon.shape"
                            :role="item.icon.role"
                            :name="item.name"
                            :title="item.label"
                            v-bind="item.attributes ?? {}"
                        />
                        {{ item.label }}
                    </label>
                    <template v-else>
                        <span class="action-menu-no-icon"></span>
                        <button
                            :name="item.name"
                            v-bind="Object.assign(item.attributes ?? {}, linkAttributes(item))"
                            v-on="linkEvents(item)"
                        >
                            {{ item.label }}
                        </button>
                    </template>
                </li>
            </ul>
        </div>
    </div>
    <div v-else>
        <a
            v-for="item in navigationItems"
            :key="item.id"
            v-bind="linkAttributes(item)"
            v-on="linkEvents(item)"
        >
            <span v-if="item.type === 'separator'" class="quiet">|</span>
            <StudipIcon
                v-else
                :title="item.label"
                :shape="item.icon.shape"
                :role="item.icon.role"
                :size="20"
            />
        </a>
    </div>
</template>