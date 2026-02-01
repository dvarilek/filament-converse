export function attachmentArea({
    uploadDropZoneRef,
    statePath,
    $wire
}) {
    return {
        isDraggingFileAttachment: false,

        uploadingFileAttachments: [],

        init() {
            this.registerFileAttachmentUploadEventListeners()
        },

        registerFileAttachmentUploadEventListeners() {
            const element = this.$refs[uploadDropZoneRef]

            if (! element) {
                console.warn('Dropzone ref ' + uploadDropZoneRef + ' not found.')
                return
            }

            new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (eventName) =>
                    element.addEventListener(
                        eventName,
                        (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                        },
                        false,
                    ),
            )

            new Set(['dragenter', 'dragover']).forEach((eventName) => {
                element.addEventListener(
                    eventName,
                    () => {
                        this.isDraggingFileAttachment = true
                    },
                    false,
                )
            })

            element.addEventListener('dragleave', (event) => {
                if (!element.contains(event.relatedTarget)) {
                    this.isDraggingFileAttachment = false
                }
            })

            element.addEventListener('drop', async (event) => {
                this.isDraggingFileAttachment = false

                await this.handleAttachmentUpload(
                    (event.dataTransfer && event.dataTransfer.files) || [],
                )
            })
        },

        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
        },

        async handleAttachmentUpload(files) {
            if (!files.length) {
                return
            }

            this.uploadingFileAttachments.push(...files)

            await $wire.uploadMultiple(
                statePath,
                files,
                () => (this.uploadingFileAttachments = []),
                () => (this.uploadingFileAttachments = []),
            )
        },
    }
}
